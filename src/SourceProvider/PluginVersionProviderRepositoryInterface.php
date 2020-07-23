<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\SourceProvider;

use Generator;
use IteratorAggregate;
use Phpcq\RepositoryBuilder\Repository\Plugin\PluginVersionInterface;

/**
 * This describes a plugin version providing repository.
 */
interface PluginVersionProviderRepositoryInterface extends IteratorAggregate, SourceRepositoryInterface
{
    /**
     * Iterate over all versions.
     *
     * @return Generator|\Phpcq\RepositoryBuilder\Repository\Plugin\PluginVersionInterface[]
     * @psalm-return Generator<PluginVersionInterface>
     */
    public function getIterator(): Generator;
}
