<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\SourceProvider\MockRepositoryInterface;

use Phpcq\RepositoryBuilder\SourceProvider\Tool\ToolVersionEnrichingRepositoryInterface;
use Psr\Log\LoggerAwareInterface;

interface ToolVersionEnrichingInterface extends ToolVersionEnrichingRepositoryInterface, LoggerAwareInterface
{
}
