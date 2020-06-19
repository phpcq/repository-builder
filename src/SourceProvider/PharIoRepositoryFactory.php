<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\SourceProvider;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class PharIoRepositoryFactory implements SourceRepositoryFactoryInterface
{
    private HttpClientInterface $httpClient;

    private string $cacheDir;

    public function __construct(HttpClientInterface $httpClient, string $cacheDir)
    {
        $this->httpClient = $httpClient;
        $this->cacheDir   = $cacheDir;
    }

    public function create(array $configuration, ToolVersionFilterRegistry $filterRegistry): SourceRepositoryInterface
    {
        assert(is_string($configuration['url']));
        return new PharIoRepository($configuration['url'], $this->cacheDir, $this->httpClient, $filterRegistry);
    }
}
