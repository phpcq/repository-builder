<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\SourceProvider\Tool\PharIo;

use Phpcq\RepositoryBuilder\SourceProvider\LoaderContext;
use Phpcq\RepositoryBuilder\SourceProvider\SourceRepositoryFactoryInterface;
use Phpcq\RepositoryBuilder\SourceProvider\SourceRepositoryInterface;
use Phpcq\RepositoryBuilder\SourceProvider\Tool\ToolVersionFilter;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @psalm-type TPharIoRepositoryConfiguration = array{
 *   tool-name: string,
 *   url: string,
 *   allowed-versions?: string,
 * }
 */
class RepositoryFactory implements SourceRepositoryFactoryInterface
{
    private HttpClientInterface $httpClient;

    private string $cacheDir;

    public function __construct(HttpClientInterface $httpClient, string $cacheDir)
    {
        $this->httpClient = $httpClient;
        $this->cacheDir   = $cacheDir;
    }

    /**
     * @param TPharIoRepositoryConfiguration $configuration
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function create(array $configuration, LoaderContext $context): SourceRepositoryInterface
    {
        $toolName = $context->getToolName() ?? $configuration['tool-name'] ?? null;
        $filter = null;
        if (null !== $toolName) {
            $filter = new ToolVersionFilter($toolName, $context->getToolConstraint() ?? '*');
            if (null !== $constraint = $configuration['allowed-versions'] ?? null) {
                $filter = new ToolVersionFilter($toolName, $constraint, $filter);
            }
        }

        return new Repository($configuration['url'], $this->cacheDir, $this->httpClient, $filter);
    }
}
