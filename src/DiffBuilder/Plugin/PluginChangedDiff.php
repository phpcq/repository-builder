<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\DiffBuilder\Plugin;

use Phpcq\RepositoryBuilder\DiffBuilder\ObjectChangedDiffInterface;
use Phpcq\RepositoryBuilder\DiffBuilder\ObjectChangedDiffTrait;
use Phpcq\RepositoryDefinition\Plugin\Plugin;

final class PluginChangedDiff implements ObjectChangedDiffInterface, PluginDiffInterface
{
    use ObjectChangedDiffTrait;

    public static function diff(Plugin $old, Plugin $new): ?PluginChangedDiff
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
            $differences[$version] = PluginVersionAddedDiff::diff($newVersion);
        }

        // 2. detect all removed versions.
        foreach ($old as $oldVersion) {
            if ($new->has($version = $oldVersion->getVersion())) {
                $toDiff[$version] = $version;
                continue;
            }
            $differences[$version] = PluginVersionRemovedDiff::diff($oldVersion);
        }

        // 3. detect all changed versions.
        foreach ($toDiff as $diffVersion) {
            $diff = PluginVersionChangedDiff::diff($old->getVersion($diffVersion), $new->getVersion($diffVersion));
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
