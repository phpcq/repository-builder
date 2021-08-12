<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\SourceProvider;

use Generator;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;

/**
 * This describes a tool version providing repository.
 */
interface ToolVersionProvidingRepositoryInterface extends SourceRepositoryInterface
{
    /**
     * Iterate over all versions.
     *
     * @psalm-return Generator<ToolVersionInterface>
     */
    public function getToolIterator(): Generator;
}
