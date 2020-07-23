<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\DiffBuilder\Plugin;

use Phpcq\RepositoryBuilder\DiffBuilder\ObjectDiffInterface;
use Phpcq\RepositoryBuilder\Repository\Plugin\Plugin;

final class PluginDiff
{
    /**
     * @param Plugin[] $oldPlugins Plugins indexed by name.
     * @param Plugin[] $newPlugins Plugins indexed by name.
     *
     * @return ObjectDiffInterface[]
     * @psalm-return list<ObjectDiffInterface> $differences
     */
    public static function diff(array $oldPlugins, array $newPlugins): array
    {
        $oldByName = [];
        foreach ($oldPlugins as $tool) {
            $oldByName[$tool->getName()] = $tool;
        }
        $newByName = [];
        foreach ($newPlugins as $tool) {
            $newByName[$tool->getName()] = $tool;
        }

        $differences = [];
        $toDiff      = [];
        // 1. detect all new versions.
        foreach ($newPlugins as $newTool) {
            if (array_key_exists($name = $newTool->getName(), $oldByName)) {
                $toDiff[$name] = $name;
                continue;
            }
            $differences[] = PluginAddedDiff::diff($newTool);
        }

        // 2. detect all removed versions.
        foreach ($oldByName as $oldTool) {
            if (array_key_exists($name = $oldTool->getName(), $newByName)) {
                $toDiff[$name] = $name;
                continue;
            }
            $differences[] = PluginRemovedDiff::diff($oldTool);
        }

        // 3. detect all changed versions.
        foreach ($toDiff as $diffTool) {
            if (null !== $diff = PluginChangedDiff::diff($oldByName[$diffTool], $newByName[$diffTool])) {
                $differences[] = $diff;
            }
        }

        return $differences;
    }
}
