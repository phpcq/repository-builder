<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\SourceProvider\MockRepositoryInterface;

use Phpcq\RepositoryBuilder\SourceProvider\Tool\ToolVersionProvidingRepositoryInterface;
use Psr\Log\LoggerAwareInterface;

interface ToolVersionProvidingInterface extends ToolVersionProvidingRepositoryInterface, LoggerAwareInterface
{
}
