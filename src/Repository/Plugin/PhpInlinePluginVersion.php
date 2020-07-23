<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Repository\Plugin;

/**
 * Inline php plugin.
 */
class PhpInlinePluginVersion extends AbstractPluginVersion
{
    private string $code;

    public function __construct(
        string $name,
        string $version,
        string $apiVersion,
        ?PluginRequirements $requirements,
        string $code
    ) {
        parent::__construct($name, $version, $apiVersion, $requirements, PluginHash::createForString($code));
        $this->code = $code;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getSignature(): ?string
    {
        // FIXME: support inline signature?
        return null;
    }
}
