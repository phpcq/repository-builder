<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\SourceProvider;

interface SourceRepositoryFactoryInterface
{
    public function create(array $configuration, ToolVersionFilterRegistry $filterRegistry): SourceRepositoryInterface;
}
