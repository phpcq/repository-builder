<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\SourceProvider\Plugin\Github;

use Generator;
use Phpcq\RepositoryBuilder\Api\GithubClient;
use Phpcq\RepositoryBuilder\Exception\DataNotAvailableException;
use Phpcq\RepositoryBuilder\SourceProvider\CompoundRepository;
use Phpcq\RepositoryBuilder\SourceProvider\LoaderContext;
use Phpcq\RepositoryBuilder\SourceProvider\SourceRepositoryFactoryInterface;
use Phpcq\RepositoryBuilder\SourceProvider\SourceRepositoryInterface;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @psalm-import-type TToolRequirementsSchema from JsonEntry
 * @psalm-import-type TPluginRequirementsSchema from JsonEntry
 *
 * @psalm-type TGithubProviderRepositoryFactoryConfiguration = array{
 *   repositories: list<string>
 * }
 *
 * @implements SourceRepositoryFactoryInterface<TGithubProviderRepositoryFactoryConfiguration>
 */
class RepositoryFactory implements SourceRepositoryFactoryInterface
{
    private HttpClientInterface $httpClient;

    private GithubClient $githubClient;

    public function __construct(HttpClientInterface $httpClient, GithubClient $githubClient)
    {
        $this->httpClient   = $httpClient;
        $this->githubClient = $githubClient;
    }

    public function create(array $configuration, LoaderContext $context): SourceRepositoryInterface
    {
        if (!is_array($sourceRepositories = $configuration['repositories'] ?? null)) {
            throw new RuntimeException('No source repositories configured');
        }

        $repositories = [];
        foreach ($sourceRepositories as $sourceRepository) {
            $jsonLoader = new JsonLoader($this->githubClient, $sourceRepository);
            $repositories[] = new Repository($jsonLoader, $this->httpClient);
            foreach ($this->processChildren($jsonLoader, $context) as $child) {
                $repositories[] = $child;
            }
        }

        return new CompoundRepository(...$repositories);
    }

    /** @return Generator<int, SourceRepositoryInterface> */
    private function processChildren(JsonLoader $jsonLoader, LoaderContext $context): Generator
    {
        $context = $context->withoutPlugin()->withoutTool();
        foreach ($jsonLoader->getJsonFileIterator() as $jsonFile) {
            try {
                $allRequirements = $jsonFile->getRequirements();
            } catch (DataNotAvailableException $exception) {
                // If no json file present, ignore this one.
                continue;
            }
            if (null !== ($requirements = $allRequirements['plugin'] ?? null)) {
                foreach ($this->processPluginRequirements($requirements, $context) as $child) {
                    yield $child;
                };
            }
            if (null !== ($requirements = $allRequirements['tool'] ?? null)) {
                foreach ($this->processToolRequirements($requirements, $context) as $child) {
                    yield $child;
                };
            }
        }
    }

    /**
     * @param TPluginRequirementsSchema $requirements
     *
     * @return Generator<int, SourceRepositoryInterface>
     */
    private function processPluginRequirements(array $requirements, LoaderContext $context): Generator
    {
        $loader = $context->getLoader();
        foreach ($requirements as $name => $requirement) {
            foreach ($requirement['sources'] ?? [] as $source) {
                $source['type'] = 'plugin-' . $source['type'];
                yield $loader->load($source, $context->withPlugin($name, $requirement['constraints'] ?? '*'));
            }
        }
    }

    /**
     * @param TToolRequirementsSchema $requirements
     *
     * @return Generator<int, SourceRepositoryInterface>
     */
    private function processToolRequirements(array $requirements, LoaderContext $context): Generator
    {
        $loader = $context->getLoader();
        foreach ($requirements as $name => $requirement) {
            foreach ($requirement['sources'] ?? [] as $source) {
                $source['type'] = 'tool-' . $source['type'];
                yield $loader->load($source, $context->withTool($name, $requirement['constraints'] ?? '*'));
            }
        }
    }
}
