<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\SourceProvider;

use Composer\Semver\Constraint\ConstraintInterface;
use Composer\Semver\VersionParser;
use Generator;
use Phpcq\RepositoryBuilder\Api\GithubClient;
use Phpcq\RepositoryBuilder\Exception\DataNotAvailableException;
use Phpcq\RepositoryBuilder\Repository\ToolVersion;
use Phpcq\RepositoryBuilder\Repository\VersionRequirement;
use UnexpectedValueException;
use function substr;

/**
 * This reads the composer.json file from the tags on github and produces the platform requirements.
 *
 * Additionally, this can produce versions from all tags.
 */
class GithubTagRequirementProviderRepository implements EnrichingRepositoryInterface, VersionProvidingRepositoryInterface
{
    private string $repositoryName;

    private string $toolName;

    private ConstraintInterface $allowedVersions;

    private GithubClient $githubClient;

    private VersionParser $versionParser;

    private array $tags = [];

    public function __construct(string $repositoryName, string $toolName, string $allowedVersions, GithubClient $githubClient)
    {
        $this->versionParser   = new VersionParser();
        $this->repositoryName  = $repositoryName;
        $this->toolName        = $toolName;
        $this->allowedVersions = $this->versionParser->parseConstraints($allowedVersions);
        $this->githubClient    = $githubClient;
    }

    public function supports(ToolVersion $version): bool
    {
        $normalizedVersion = $this->versionParser->normalize($version->getVersion());
        return ($version->getName() === $this->toolName) && isset($this->tags[$normalizedVersion]);
    }

    public function enrich(ToolVersion $version): void
    {
        $normalizedVersion = $this->versionParser->normalize($version->getVersion());
        $tag               = $this->tags[$normalizedVersion];
        $composerJson      = $this->githubClient->fetchFile($this->repositoryName, $tag['tag_name'], 'composer.json');

        foreach ($composerJson['require'] as $requirement => $constraint) {
            if ('php' === $requirement || 0 === strncmp($requirement, 'ext-', 4)) {
                $version->getRequirements()->add(new VersionRequirement($requirement, $constraint));
            }
        }
    }

    public function isFresh(): bool
    {
        return [] !== $this->tags;
    }

    public function refresh(): void
    {
        $this->tags = [];
        // Download all tags... then download all composer.json files.
        $data = $this->githubClient->fetchTags($this->repositoryName);

        foreach ($data as $entry) {
            try {
                $tagName = substr($entry['ref'], 10);
                $version = $this->versionParser->normalize($tagName);
                if (!$this->allowedVersions->matches($this->versionParser->parseConstraints($version))) {
                    continue;
                }
                $entry['tag_name']    = $tagName;
                $entry['version']     = $version;
                $this->tags[$version] = $entry;
            } catch (UnexpectedValueException $exception) {
                // Ignore tags not matching semver
            }
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
                // Fixme: We assume that only one phar and signature is provided
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
                [],
                null,
                $signatureUrl,
                null
            );
        }
    }
}
