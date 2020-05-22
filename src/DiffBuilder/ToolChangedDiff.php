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
            $changes = [];
            foreach ($new->getIterator() as $version) {
                $changes[$version->getVersion()] = VersionAddedDiff::diff($version);
            }
            return new ToolChangedDiff($new->getName(), $changes);
        }

        // Tool got removed, add all versions as removed.
        if (null === $new) {
            $changes = [];
            foreach ($old->getIterator() as $version) {
                $changes[$version->getVersion()] = VersionRemovedDiff::diff($version);
            }
            return new ToolChangedDiff($old->getName(), $changes);
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
        foreach ($this->differences as $change) {
            $result[] = $change->asString($prefix . '  ');
        }

        return $prefix . 'Changes for ' . $this->toolName . ':' . "\n" . implode('', $result);
    }

    private static function deepCompare(Tool $old, Tool $new): ?ToolChangedDiff
    {
        $changes = [];
        $toDiff  = [];
        // 1. detect all new versions.
        foreach ($new as $newVersion) {
            if ($old->has($version = $newVersion->getVersion())) {
                $toDiff[$version] = $version;
                continue;
            }
            $changes[$version] = VersionAddedDiff::diff($newVersion);
        }

        // 2. detect all removed versions.
        foreach ($old as $oldVersion) {
            if ($new->has($version = $oldVersion->getVersion())) {
                $toDiff[$version] = $version;
                continue;
            }
            $changes[$version] = VersionRemovedDiff::diff($oldVersion);
        }

        // 3. detect all changed versions.
        foreach ($toDiff as $diffVersion) {
            if ($diff = VersionChangedDiff::diff($old->getVersion($diffVersion), $new->getVersion($diffVersion))) {
                $changes[$diffVersion] = $diff;
            }
        }

        if (empty($changes)) {
            return null;
        }

        return new self($old->getName(), $changes);
    }
}
