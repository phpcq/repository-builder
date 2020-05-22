<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\DiffBuilder;

trait ToolDiffTrait
{
    private string $toolName;

    /**
     * @var DiffInterface[]
     */
    private array $differences;

    public function getToolName(): string
    {
        return $this->toolName;
    }

    public function getDifferences(): array
    {
        return $this->differences;
    }

    public function __toString(): string
    {
        return $this->asString('');
    }

    private function __construct(string $toolName, array $differences)
    {
        $this->toolName    = $toolName;
        $this->differences = $differences;
        ksort($this->differences);
    }
}
