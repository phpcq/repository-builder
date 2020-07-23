<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Repository\Plugin;

use Generator;
use InvalidArgumentException;
use IteratorAggregate;
use LogicException;
use Traversable;

/**
 * This holds all versions of a plugin.
 *
 * @template-implements IteratorAggregate<int, PluginVersionInterface>
 */
class Plugin implements IteratorAggregate
{
    /**
     * The name of the plugin.
     */
    private string $name;

    /**
     * All versions of the plugin.
     *
     * @var PluginVersionInterface[]
     */
    private array $versions = [];

    /**
     * Create a new instance.
     *
     * @param string $name The name of the plugin.
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Retrieve name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function addVersion(PluginVersionInterface $version): void
    {
        if ($version->getName() !== $this->name) {
            throw new InvalidArgumentException('Plugin name mismatch: ' . $version->getName());
        }
        if ($this->has($version->getVersion())) {
            throw new LogicException('Version already added: ' . $version->getVersion());
        }

        $this->versions[$version->getVersion()] = $version;
    }

    public function getVersion(string $version): PluginVersionInterface
    {
        if (!$this->has($version)) {
            throw new LogicException('Version not added: ' . $version);
        }
        return $this->versions[$version];
    }

    public function has(string $version): bool
    {
        return isset($this->versions[$version]);
    }

    public function isEmpty(): bool
    {
        return empty($this->versions);
    }

    /**
     * Iterate over all versions.
     *
     * @return Generator|Traversable|PluginVersionInterface[]
     *
     * @psalm-return Generator<PluginVersionInterface>
     */
    public function getIterator()
    {
        foreach ($this->versions as $version) {
            yield $version;
        }
    }
}
