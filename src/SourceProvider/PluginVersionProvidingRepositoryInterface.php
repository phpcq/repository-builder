<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\SourceProvider;

use Generator;
use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;

/**
 * This describes a tool version providing repository.
 * @SuppressWarnings(PHPMD.LongClassName)
 */
interface PluginVersionProvidingRepositoryInterface extends SourceRepositoryInterface
{
    /**
     * Iterate over all versions.
     *
     * @return Generator<PluginVersionInterface>
     */
    public function getPluginIterator(): Generator;
}
