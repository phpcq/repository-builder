<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test;

use Phpcq\RepositoryBuilder\SourceProvider\Tool\ToolVersionEnrichingRepositoryInterface;
use Phpcq\RepositoryBuilder\SourceProvider\ToolVersionProvidingRepositoryInterface;

/** @SuppressWarnings(PHPMD.LongClassName) */
interface ToolVersionProvidingAndEnrichingRepositoryInterface extends
    ToolVersionProvidingRepositoryInterface,
    ToolVersionEnrichingRepositoryInterface
{
}
