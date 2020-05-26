<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\DiffBuilder;

use LogicException;
use Phpcq\RepositoryBuilder\Repository\Tool;

final class Diff implements DiffInterface
{
    /**
     * @var DiffInterface[]|null
     */
    private ?array $differences;

    /**
     * @param Tool[]|null $old Tools indexed by tool name.
     * @param Tool[]|null $new Tools indexed by tool name.
     *
     * @return Diff|null
     */
    public static function diff(?array $old, ?array $new): ?Diff
    {
        if (null === $old && null === $new) {
            throw new LogicException('new value and old value must not both be null.');
        }

        // New repository, add all tools as new.
        if (null === $old) {
            if (empty($new)) {
                return null;
            }
            $differences = [];
            foreach ($new as $tool) {
                $differences[$tool->getName()] = ToolAddedDiff::diff($tool);
            }
            return new Diff($differences);
        }

        // Tool got removed, add all versions as removed.
        if (null === $new) {
            if (empty($old)) {
                return null;
            }
            $differences = [];
            foreach ($old as $tool) {
                $differences[$tool->getName()] = ToolRemovedDiff::diff($tool);
            }
            return new Diff($differences);
        }

        return self::deepCompare($old, $new);
    }

    public function __toString(): string
    {
        return $this->asString('');
    }

    public function asString(string $prefix): string
    {
        if (empty($this->differences)) {
            return '';
        }

        $result = [];
        foreach ($this->differences as $difference) {
            $result[] = $difference->asString($prefix . '  ');
        }

        return $prefix . 'Changes in repository:' . "\n" . implode('', $result);
    }

    /**
     * @param Tool[] $old
     * @param Tool[] $new
     *
     * @return Diff|null
     */
    private static function deepCompare(array $old, array $new): ?Diff
    {
        $differences = [];
        $toDiff  = [];
        // 1. detect all new versions.
        foreach ($new as $newTool) {
            if (array_key_exists($name = $newTool->getName(), $old)) {
                $toDiff[$name] = $name;
                continue;
            }
            $differences[$name] = ToolAddedDiff::diff($newTool);
        }

        // 2. detect all removed versions.
        foreach ($old as $oldTool) {
            if (array_key_exists($name = $oldTool->getName(), $new)) {
                $toDiff[$name] = $name;
                continue;
            }
            $differences[$name] = ToolRemovedDiff::diff($oldTool);
        }

        // 3. detect all changed versions.
        foreach ($toDiff as $diffTool) {
            if ($diff = ToolChangedDiff::diff($old[$diffTool] ?? null, $new[$diffTool] ?? null)) {
                $differences[$diffTool] = $diff;
            }
        }

        if (empty($differences)) {
            return null;
        }

        return new self($differences);
    }

    private function __construct(array $differences)
    {
        $this->differences = $differences;
        ksort($this->differences);
    }
}
