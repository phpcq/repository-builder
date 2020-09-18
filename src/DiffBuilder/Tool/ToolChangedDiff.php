<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\DiffBuilder\Tool;

use Phpcq\RepositoryBuilder\DiffBuilder\ObjectChangedDiffInterface;
use Phpcq\RepositoryBuilder\DiffBuilder\ObjectChangedDiffTrait;
use Phpcq\RepositoryDefinition\Tool\Tool;

final class ToolChangedDiff implements ObjectChangedDiffInterface, ToolDiffInterface
{
    use ObjectChangedDiffTrait;

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
            $differences[$version] = ToolVersionAddedDiff::diff($newVersion);
        }

        // 2. detect all removed versions.
        foreach ($old as $oldVersion) {
            if ($new->has($version = $oldVersion->getVersion())) {
                $toDiff[$version] = $version;
                continue;
            }
            $differences[$version] = ToolVersionRemovedDiff::diff($oldVersion);
        }

        // 3. detect all changed versions.
        foreach ($toDiff as $diffVersion) {
            $diff = ToolVersionChangedDiff::diff($old->getVersion($diffVersion), $new->getVersion($diffVersion));
            if (null !== $diff) {
                $differences[$diffVersion] = $diff;
            }
        }

        if (empty($differences)) {
            return null;
        }

        return new self($old->getName(), $differences);
    }
}
