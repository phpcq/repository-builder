<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\SourceProvider;

use Generator;
use Phpcq\RepositoryDefinition\Tool\ToolVersion;

/**
 * This describes a tool version providing repository.
 */
interface ToolVersionProvidingRepositoryInterface extends SourceRepositoryInterface
{
    /**
     * Iterate over all versions.
     *
     * @psalm-return Generator<ToolVersion>
     */
    public function getToolIterator(): Generator;
}
