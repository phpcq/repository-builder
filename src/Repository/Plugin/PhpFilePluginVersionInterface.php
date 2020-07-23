<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Repository\Plugin;

/**
 * Plugin with url.
 */
interface PhpFilePluginVersionInterface extends PluginVersionInterface
{
    public function getFilePath(): string;

    public function getSignaturePath(): ?string;
}
