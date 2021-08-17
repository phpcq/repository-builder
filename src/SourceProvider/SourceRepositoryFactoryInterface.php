<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\SourceProvider;

interface SourceRepositoryFactoryInterface
{
    /** @psalm-param array<string, mixed> $configuration */
    public function create(array $configuration, LoaderContext $context): SourceRepositoryInterface;
}
