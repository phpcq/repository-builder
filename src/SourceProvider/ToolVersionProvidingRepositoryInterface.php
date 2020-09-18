<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\SourceProvider;

use Generator;
use IteratorAggregate;
use Phpcq\RepositoryDefinition\Tool\ToolVersion;

/**
 * This describes a tool version providing repository.
 */
interface ToolVersionProvidingRepositoryInterface extends IteratorAggregate, SourceRepositoryInterface
{
    /**
     * Iterate over all versions.
     *
     * @return Generator|ToolVersion[]
     * @psalm-return Generator<ToolVersion>
     */
    public function getIterator(): Generator;
}
