<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\SourceProvider;

use Generator;
use IteratorAggregate;
use Phpcq\RepositoryBuilder\Repository\ToolVersion;

/**
 * This describes a source repository.
 */
interface VersionProvidingRepositoryInterface extends IteratorAggregate, SourceRepositoryInterface
{
    /**
     * Iterate over all versions.
     *
     * @return Generator|ToolVersion
     */
    public function getIterator(): Generator;
}