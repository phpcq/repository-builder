<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\SourceProvider\MockRepositoryInterface;

use Phpcq\RepositoryBuilder\SourceProvider\PluginVersionProvidingRepositoryInterface;
use Psr\Log\LoggerAwareInterface;

interface PluginVersionProvidingInterface extends PluginVersionProvidingRepositoryInterface, LoggerAwareInterface
{
}
