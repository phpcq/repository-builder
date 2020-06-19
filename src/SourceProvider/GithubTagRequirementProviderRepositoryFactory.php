<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\SourceProvider;

use Phpcq\RepositoryBuilder\Api\GithubClient;

class GithubTagRequirementProviderRepositoryFactory implements SourceRepositoryFactoryInterface
{
    private GithubClient $githubClient;

    public function __construct(GithubClient $githubClient)
    {
        $this->githubClient = $githubClient;
    }

    public function create(array $configuration, ToolVersionFilterRegistry $filterRegistry): SourceRepositoryInterface
    {
        $filter = $filterRegistry->getFilterForTool($configuration['tool_name']);
        if (isset($configuration['allowed_versions'])) {
            $filter = new ToolVersionFilter($configuration['tool_name'], $configuration['allowed_versions'], $filter);
        }

        return new GithubTagRequirementProviderRepository(
            $configuration['repository'],
            $configuration['tool_name'],
            $configuration['file_pattern'] ?? ($configuration['tool_name'] . '.*'),
            $filter,
            $this->githubClient
        );
    }
}
