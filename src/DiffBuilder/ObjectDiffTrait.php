<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\DiffBuilder;

trait ObjectDiffTrait
{
    private string $name;

    /**
     * @var ObjectDiffInterface[]
     * @psalm-var array<string, ObjectVersionDiffInterface>
     */
    private array $differences;

    public function getName(): string
    {
        return $this->name;
    }

    /** @psalm-return array<string, ObjectVersionDiffInterface> */
    public function getDifferences(): array
    {
        return $this->differences;
    }

    public function __toString(): string
    {
        return $this->asString('');
    }

    /** @psalm-param array<string, ObjectVersionDiffInterface> $differences */
    private function __construct(string $name, array $differences)
    {
        $this->name        = $name;
        $this->differences = $differences;
        ksort($this->differences);
    }
}
