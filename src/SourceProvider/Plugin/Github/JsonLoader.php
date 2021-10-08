<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\SourceProvider\Plugin\Github;

use Composer\Semver\VersionParser;
use Generator;
use Phpcq\RepositoryBuilder\Api\GithubClient;
use UnexpectedValueException;

final class JsonLoader
{
    private GithubClient $githubClient;

    private string $repository;

    private VersionParser $versionParser;

    public function __construct(GithubClient $githubClient, string $repository)
    {
        $this->githubClient  = $githubClient;
        $this->repository    = $repository;
        $this->versionParser = new VersionParser();
    }

    /**
     * @return JsonEntry[]
     * @psalm-return Generator<int, JsonEntry>
     */
    public function getJsonFileIterator(): Generator
    {
        // Download the configs from there.
        $tagList = $this->githubClient->fetchTags($this->repository);
        foreach ($tagList as $tag) {
            try {
                $tagName = substr($tag['ref'], 10);
                $version = $this->versionParser->normalize($tagName);
                // FIXME: need tag filter here for allowed tags.
                yield new JsonEntry(
                    $this->repository,
                    $tagName,
                    $version,
                    $this->githubClient
                );
            } catch (UnexpectedValueException $exception) {
                // Ignore tags not matching semver
                continue;
            }
        }
    }
}
