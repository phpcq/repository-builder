<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\DiffBuilder;

use Phpcq\RepositoryBuilder\Repository\InlineBootstrap;
use Phpcq\RepositoryBuilder\Repository\ToolVersion;
use RuntimeException;

trait VersionDiffTrait
{
    private string $toolName;

    private string $version;

    private array $differences;

    public function getToolName(): string
    {
        return $this->toolName;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getDifferences(): array
    {
        return $this->differences;
    }

    public function __toString(): string
    {
        return $this->asString('');
    }

    private function __construct(string $toolName, string $version, array $differences)
    {
        $this->toolName    = $toolName;
        $this->version     = $version;
        $this->differences = $differences;
    }

    private static function reqToStr(ToolVersion $toolVersion): string
    {
        $requirements = $toolVersion->getRequirements();
        $result       = [];
        foreach ($requirements->getIterator() as $requirement) {
            $result[] = $requirement->getName() . ':' . $requirement->getConstraint();
        }

        return implode(', ', $result);
    }

    private static function hashToStr(ToolVersion $toolVersion): string
    {
        $hash = $toolVersion->getHash();
        if (null === $hash) {
            return '';
        }

        return $hash->getType() . ':' . $hash->getValue();
    }

    private static function bootstrapToStr(ToolVersion $toolVersion): string
    {
        $bootstrap = $toolVersion->getBootstrap();
        if (null === $bootstrap) {
            return '';
        }

        if (!$bootstrap instanceof InlineBootstrap) {
            throw new RuntimeException('Unexpected bootstrap class encountered ' . get_class($bootstrap));
        }

        return 'inline:' . $bootstrap->getPluginVersion() .  ':' . md5($bootstrap->getCode());
    }
}
