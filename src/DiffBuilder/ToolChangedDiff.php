<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\DiffBuilder;

use Phpcq\RepositoryBuilder\Repository\Tool;

final class ToolChangedDiff implements DiffInterface
{
    use ToolDiffTrait;

    public static function diff(Tool $old, Tool $new): ?ToolChangedDiff
    {
        assert($old->getName() === $new->getName());
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
            $diff = VersionChangedDiff::diff($old->getVersion($diffVersion), $new->getVersion($diffVersion));
            if (null !== $diff) {
                $differences[$diffVersion] = $diff;
            }
        }

        if (empty($differences)) {
            return null;
        }

        return new self($old->getName(), $differences);
    }

    public function asString(string $prefix): string
    {
        $result = [];
        foreach ($this->differences as $difference) {
            $result[] = $difference->asString($prefix . '  ');
        }

        return $prefix . 'Changes for ' . $this->toolName . ':' . "\n" . implode('', $result);
    }
}
