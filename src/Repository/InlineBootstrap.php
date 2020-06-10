<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Repository;

use RuntimeException;

/**
 * Remote bootstrap loader.
 */
class InlineBootstrap implements BootstrapInterface
{
    private string $code;

    private ?BootstrapHash $hash;

    public function __construct(string $version, string $code, ?BootstrapHash $hash)
    {
        if ($version !== '1.0.0') {
            throw new RuntimeException('Invalid version string: ' . $version);
        }

        $this->code = $code;
        $this->hash = $hash;
    }

    public function getPluginVersion(): string
    {
        return '1.0.0';
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getHash(): ?BootstrapHash
    {
        return $this->hash;
    }
}
