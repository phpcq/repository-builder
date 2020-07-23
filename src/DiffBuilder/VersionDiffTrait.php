<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\DiffBuilder;

trait VersionDiffTrait
{
    private string $name;

    private string $version;

    /**
     * @var PropertyDifference[]
     * @psalm-var list<PropertyDifference>
     */
    private array $differences;

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @return PropertyDifference[]
     * @psalm-return list<PropertyDifference>
     */
    public function getDifferences(): array
    {
        return $this->differences;
    }

    public function __toString(): string
    {
        return $this->asString('');
    }

    /**
     * @param PropertyDifference[] $differences
     * @psalm-param list<PropertyDifference> $differences
     */
    private function __construct(string $name, string $version, array $differences)
    {
        $this->name    = $name;
        $this->version = $version;
        $this->differences = $differences;
    }
}
