<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Repository;

/**
 * Describes a bootstrap.
 */
interface BootstrapInterface
{
    public function getPluginVersion(): string;

    public function getCode(): string;
}
