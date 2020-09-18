<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\SourceProvider;

use Composer\Semver\VersionParser;
use Generator;
use Phpcq\RepositoryBuilder\Api\GithubClient;
use Phpcq\RepositoryBuilder\Exception\DataNotAvailableException;
use Phpcq\RepositoryDefinition\Tool\ToolVersion;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;
use Phpcq\RepositoryDefinition\VersionRequirement;
use UnexpectedValueException;

use function preg_match;
use function str_replace;
use function substr;

/**
 * This reads the composer.json file from the tags on github and produces the platform requirements.
 *
 * Additionally, this can produce versions from all tags.
 *
 * @psalm-type TTag = array{
 *   ref: string,
 *   tag_name: string,
 *   version: string
 * }
 * @psalm-type TTagList = array<string, TTag>
 * @psalm-type TTagInfo = array{
 *   assets: list<array{
 *     name: string,
 *     browser_download_url: string,
 *   }>
 * }
 *
 */
class GithubTagRequirementProviderRepository implements
    ToolVersionEnrichingRepositoryInterface,
    ToolVersionProvidingRepositoryInterface
{
    private string $repositoryName;

    private string $toolName;

    private ToolVersionFilter $versionFilter;

    private GithubClient $githubClient;

    private VersionParser $versionParser;

    /** @psalm-var TTagList */
    private array $tags = [];

    private string $fileNameRegex;

    public function __construct(
        string $repositoryName,
        string $toolName,
        string $fileNamePattern,
        ToolVersionFilter $versionFilter,
        GithubClient $githubClient
    ) {
        $this->versionParser   = new VersionParser();
        $this->repositoryName  = $repositoryName;
        $this->toolName        = $toolName;
        $this->versionFilter   = $versionFilter;
        $this->githubClient    = $githubClient;
        $this->fileNameRegex   = '#' . str_replace('#', '\\#', $fileNamePattern) . '#i';
    }

    public function supports(ToolVersionInterface $version): bool
    {
        $normalizedVersion = $this->versionParser->normalize($version->getVersion());
        return ($version->getName() === $this->toolName) && isset($this->tags[$normalizedVersion]);
    }

    public function enrich(ToolVersionInterface $version): void
    {
        $normalizedVersion = $this->versionParser->normalize($version->getVersion());
        $tag               = $this->tags[$normalizedVersion] ?? null;

        if (null === $tag) {
            return;
        }

        $composerJson    = $this->githubClient->fetchFile($this->repositoryName, $tag['tag_name'], 'composer.json');
        $phpRequirements = $version->getRequirements()->getPhpRequirements();
        /** @psalm-var array{require: array<string, string>} $composerJson */
        foreach ($composerJson['require'] as $requirement => $constraint) {
            if ('php' === $requirement || 0 === strncmp($requirement, 'ext-', 4)) {
                $phpRequirements->add(new VersionRequirement($requirement, $constraint));
            }
        }

        // Push information we have.
        // FIXME: should enrich all the info instead of doing it in getIterator().
    }

    public function isFresh(): bool
    {
        return [] !== $this->tags;
    }

    public function refresh(): void
    {
        $this->tags = [];
        // Download all tags... then download all composer.json files.
        /** @psalm-var TTagList $data */
        $data = $this->githubClient->fetchTags($this->repositoryName);

        foreach ($data as $entry) {
            try {
                $tagName = substr($entry['ref'], 10);
                $version = $this->versionParser->normalize($tagName);
            } catch (UnexpectedValueException $exception) {
                // Ignore tags not matching semver
                continue;
            }
            if (!$this->versionFilter->accepts($version)) {
                continue;
            }
            $entry['tag_name']    = $tagName;
            $entry['version']     = $version;
            $this->tags[$version] = $entry;
        }
    }

    public function getIterator(): Generator
    {
        if ([] === $this->tags) {
            $this->refresh();
        }
        foreach ($this->tags as $tag) {
            // Obtain release by tag name.
            try {
                /** @psalm-var TTagInfo $data */
                $data = $this->githubClient->fetchTag($this->repositoryName, $tag['tag_name']);
            } catch (DataNotAvailableException $exception) {
                if ($exception->getCode() === 404) {
                    continue;
                }
                throw $exception;
            }
            $pharUrl = null;
            $signatureUrl = null;
            foreach ($data['assets'] as $asset) {
                if (! preg_match($this->fileNameRegex, $asset['name'])) {
                    continue;
                }

                if ('.phar' === substr($asset['name'], -5)) {
                    $pharUrl = $asset['browser_download_url'];
                    continue;
                }

                if ('.asc' === substr($asset['name'], -4)) {
                    $signatureUrl = $asset['browser_download_url'];
                    continue;
                }
            }

            // Walk the assets and try to determine the phar-url.
            yield new ToolVersion(
                $this->toolName,
                $tag['tag_name'],
                $pharUrl,
                null,
                null,
                $signatureUrl,
            );
        }
    }
}
