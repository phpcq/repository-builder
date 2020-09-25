<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\DiffBuilder;

class PropertyDifference
{
    private string $name;
    private ?string $oldValue;
    private ?string $newValue;

    public static function changed(string $name, ?string $oldValue, ?string $newValue): self
    {
        return new self($name, $oldValue, $newValue);
    }

    public static function added(string $name, ?string $newValue): self
    {
        return new self($name, null, $newValue);
    }

    public static function removed(string $name, ?string $oldValue): self
    {
        return new self($name, $oldValue, null);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getOldValue(): ?string
    {
        return $this->oldValue;
    }

    public function getNewValue(): ?string
    {
        return $this->newValue;
    }

    private function __construct(string $name, ?string $oldValue, ?string $newValue)
    {
        $this->name     = $name;
        $this->oldValue = $oldValue;
        $this->newValue = $newValue;
    }
}
