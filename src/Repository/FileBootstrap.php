<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Repository;

use RuntimeException;

/**
 * Remote bootstrap loader.
 */
class FileBootstrap implements BootstrapInterface
{
    private string $filePath;

    public function __construct(string $version, string $filePath)
    {
        if ($version !== '1.0.0') {
            throw new RuntimeException('Invalid version string: ' . $version);
        }

        $this->filePath = $filePath;
    }

    public function getPluginVersion(): string
    {
        return '1.0.0';
    }

    public function getCode(): string
    {
        return file_get_contents($this->filePath);
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }
}
