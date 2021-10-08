<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\SourceProvider\Plugin\Github;

use Phpcq\RepositoryBuilder\Api\GithubClient;

/**
 * @psalm-type TPhpPlatformRequirementsSchema = array<string, string>
 * @psalm-type TToolSourceGithubSchema = array{
 *   type: 'github',
 *   allowed-versions?: string,
 *   repository: string,
 * }
 * @psalm-type TToolSourcePharIoSchema = array{
 *   type: 'phar.io',
 *   allowed-versions?: string,
 *   url: string,
 * }
 * @psalm-type TToolSourceSchema = TToolSourceGithubSchema|TToolSourcePharIoSchema
 * @psalm-type TToolRequirementSchema = array{constraints: string, sources: list<TToolSourceSchema>}
 * @psalm-type TToolRequirementsSchema = array<string, TToolRequirementSchema>
 * @psalm-type TPluginSourceGithubSchema = array{
 *   type: 'github',
 *   allowed-versions?: string,
 *   repository: string,
 * }
 * @psalm-type TPluginSourceSchema = TPluginSourceGithubSchema
 * @psalm-type TPluginRequirementSchema = array{constraints: string, sources: list<TPluginSourceSchema>}
 * @psalm-type TPluginRequirementsSchema = array<string, TPluginRequirementSchema>
 * @psalm-type TComposerRequirementsSchema = array<string, string>
 * @psalm-type TRequirementsSchema = array{
 *   php?: TPhpPlatformRequirementsSchema,
 *   tool?: TToolRequirementsSchema,
 *   plugin?: TPluginRequirementsSchema,
 *   composer?: TComposerRequirementsSchema,
 * }
 * @psalm-type TPluginSchema = array{
 *   name: string,
 *   type: "php-file" | "phar",
 *   url: string,
 *   signature?: string,
 *   api-version: string,
 *   requirements?: TRequirementsSchema
 * }
 */
class JsonEntry
{
    private GithubClient $githubClient;

    private string $repository;

    private string $tagName;

    private string $version;

    /** @var TPluginSchema|null */
    private ?array $contents = null;

    public function __construct(string $repository, string $tagName, string $version, GithubClient $githubClient)
    {
        $this->repository   = $repository;
        $this->tagName      = $tagName;
        $this->version      = $version;
        $this->githubClient = $githubClient;
    }

    public function getRepository(): string
    {
        return $this->repository;
    }

    public function getTagName(): string
    {
        return $this->tagName;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getName(): string
    {
        return $this->getContents()['name'];
    }

    public function getType(): string
    {
        return $this->getContents()['type'];
    }

    public function getPluginUrl(bool $absolute): string
    {
        $uri = $this->getContents()['url'];
        if ($absolute) {
            // Must be relative to repository then.
            return $this->makeAbsolute($uri);
        }

        return $uri;
    }

    public function getSignatureUrl(bool $absolute): ?string
    {
        $uri = $this->getContents()['signature'] ?? null;
        if (null === $uri) {
            return null;
        }
        if ($absolute) {
            // Must be relative to repository then.
            return $this->makeAbsolute($uri);
        }

        return $uri;
    }

    public function getApiVersion(): string
    {
        return $this->getContents()['api-version'];
    }

    /** @return TRequirementsSchema|null */
    public function getRequirements(): ?array
    {
        return $this->getContents()['requirements'] ?? null;
    }

    /** @return TPluginSchema */
    public function getContents(): array
    {
        if (null === $this->contents) {
            /** @var TPluginSchema $pluginJson */
            $pluginJson = $this->githubClient->fetchFile($this->repository, $this->tagName, 'phpcq-plugin.json');

            $this->contents = $pluginJson;
        }

        return $this->contents;
    }

    private function makeAbsolute(string $uri): string
    {
        $scheme = parse_url($uri, PHP_URL_SCHEME);
        if (empty($scheme)) {
            // Must be relative to repository then.
            return $this->githubClient->fileUri($this->repository, $this->tagName, $uri);
        }

        return $uri;
    }
}
