<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\DiffBuilder;

use LogicException;
use Phpcq\RepositoryBuilder\Repository\Tool;

final class ToolChangedDiff implements DiffInterface
{
    use ToolDiffTrait;

    public static function diff(?Tool $old, ?Tool $new): ?ToolChangedDiff
    {
        if (null === $old && null === $new) {
            throw new LogicException('new value and old value must not both be null.');
        }

        // New tool, add all versions as new.
        if (null === $old) {
            $differences = [];
            foreach ($new->getIterator() as $version) {
                $differences[$version->getVersion()] = VersionAddedDiff::diff($version);
            }
            return new ToolChangedDiff($new->getName(), $differences);
        }

        // Tool got removed, add all versions as removed.
        if (null === $new) {
            $differences = [];
            foreach ($old->getIterator() as $version) {
                $differences[$version->getVersion()] = VersionRemovedDiff::diff($version);
            }
            return new ToolChangedDiff($old->getName(), $differences);
        }

        assert($old->getName() === $new->getName());
        return self::deepCompare($old, $new);
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

        return $prefix . 'Changes for ' . $this->toolName . ':' . "\n" . implode('', $result);
    }

    private static function deepCompare(Tool $old, Tool $new): ?ToolChangedDiff
    {
        $differences = [];
        $toDiff  = [];
        // 1. detect all new versions.
        foreach ($new as $newVersion) {
            if ($old->has($version = $newVersion->getVersion())) {
                $toDiff[$version] = $version;
                continue;
            }
            $differences[$version] = VersionAddedDiff::diff($newVersion);
        }

        // 2. detect all removed versions.
        foreach ($old as $oldVersion) {
            if ($new->has($version = $oldVersion->getVersion())) {
                $toDiff[$version] = $version;
                continue;
            }
            $differences[$version] = VersionRemovedDiff::diff($oldVersion);
        }

        // 3. detect all changed versions.
        foreach ($toDiff as $diffVersion) {
            if ($diff = VersionChangedDiff::diff($old->getVersion($diffVersion), $new->getVersion($diffVersion))) {
                $differences[$diffVersion] = $diff;
            }
        }

        if (empty($differences)) {
            return null;
        }

        return new self($old->getName(), $differences);
    }
}
