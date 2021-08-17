<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\SourceProvider\Tool\Github;

use Phpcq\RepositoryBuilder\Api\GithubClient;
use Phpcq\RepositoryBuilder\SourceProvider\LoaderContext;
use Phpcq\RepositoryBuilder\SourceProvider\SourceRepositoryFactoryInterface;
use Phpcq\RepositoryBuilder\SourceProvider\SourceRepositoryInterface;
use Phpcq\RepositoryBuilder\SourceProvider\ToolVersionFilter;
use RuntimeException;

/**
 * @psalm-type TGithubTagProviderRepositoryFactoryConfiguration = array{
 *   tool-name: string,
 *   allowed-versions?: string,
 *   repository: string,
 *   file-pattern?: string,
 * }
 * @SuppressWarnings(PHPMD.LongClassName)
 */
class TagProviderRepositoryFactory implements SourceRepositoryFactoryInterface
{
    private GithubClient $githubClient;

    public function __construct(GithubClient $githubClient)
    {
        $this->githubClient = $githubClient;
    }

    /**
     * @psalm-param TGithubTagProviderRepositoryFactoryConfiguration $configuration
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function create(array $configuration, LoaderContext $context): SourceRepositoryInterface
    {
        $toolName = $context->getToolName() ?? $configuration['tool-name'] ?? null;
        if (null === $toolName) {
            throw new RuntimeException('Can not determine tool name');
        }

        $filter = new ToolVersionFilter($toolName, $context->getToolConstraint() ?? '*');
        if (null !== $constraint = $configuration['allowed-versions'] ?? null) {
            $filter = new ToolVersionFilter($toolName, $constraint, $filter);
        }

        return new TagProviderRepository(
            $configuration['repository'],
            $toolName,
            $configuration['file-pattern'] ?? ($toolName . '.*'),
            $filter,
            $this->githubClient
        );
    }
}
