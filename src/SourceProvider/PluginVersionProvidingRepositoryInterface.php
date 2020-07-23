<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\SourceProvider;

use Generator;
use IteratorAggregate;
use Phpcq\RepositoryBuilder\Repository\Plugin\PluginVersionInterface;

/**
 * This describes a tool version providing repository.
 */
interface PluginVersionProvidingRepositoryInterface extends IteratorAggregate, SourceRepositoryInterface
{
    /**
     * Iterate over all versions.
     *
     * @return Generator|PluginVersionInterface[]
     * @psalm-return Generator<PluginVersionInterface>
     */
    public function getIterator(): Generator;
}
