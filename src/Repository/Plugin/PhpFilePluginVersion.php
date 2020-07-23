<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Repository\Plugin;

/**
 * Plugin with url.
 */
class PhpFilePluginVersion extends AbstractPluginVersion implements PhpFilePluginVersionInterface
{
    private string $filePath;

    private ?string $signaturePath;

    public function __construct(
        string $name,
        string $version,
        string $apiVersion,
        ?PluginRequirements $requirements,
        string $filePath,
        ?string $signaturePath = null,
        ?PluginHash $hash = null
    ) {
        $this->filePath      = $filePath;
        $this->signaturePath = $signaturePath;
        parent::__construct(
            $name,
            $version,
            $apiVersion,
            $requirements,
            $hash ?? PluginHash::createForFile($this->filePath)
        );
    }

    public function getCode(): string
    {
        return file_get_contents($this->filePath);
    }

    public function getSignature(): ?string
    {
        return $this->signaturePath ? file_get_contents($this->signaturePath) : null;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function getSignaturePath(): ?string
    {
        return $this->signaturePath;
    }
}
