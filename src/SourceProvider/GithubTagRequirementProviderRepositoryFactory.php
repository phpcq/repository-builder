<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\SourceProvider;

use Phpcq\RepositoryBuilder\Api\GithubClient;

class GithubTagRequirementProviderRepositoryFactory
{
    private GithubClient $githubClient;

    public function __construct(GithubClient $githubClient)
    {
        $this->githubClient = $githubClient;
    }

    public function create(array $configuration): SourceRepositoryInterface
    {
        return new GithubTagRequirementProviderRepository(
            $configuration['repository'],
            $configuration['tool_name'],
            $this->githubClient
        );
    }
}