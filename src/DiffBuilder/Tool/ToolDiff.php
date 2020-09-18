<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\DiffBuilder\Tool;

use Phpcq\RepositoryBuilder\DiffBuilder\ObjectDiffInterface;
use Phpcq\RepositoryDefinition\Tool\Tool;

final class ToolDiff
{
    /**
     * @param Tool[] $oldTools Tools indexed by tool name.
     * @param Tool[] $newTools Tools indexed by tool name.
     *
     * @return ObjectDiffInterface[]
     * @psalm-return list<ObjectDiffInterface> $differences
     */
    public static function diff(array $oldTools, array $newTools): array
    {
        $oldByName = [];
        foreach ($oldTools as $tool) {
            $oldByName[$tool->getName()] = $tool;
        }
        $newByName = [];
        foreach ($newTools as $tool) {
            $newByName[$tool->getName()] = $tool;
        }

        $differences = [];
        $toDiff      = [];
        // 1. detect all new versions.
        foreach ($newByName as $newTool) {
            if (array_key_exists($name = $newTool->getName(), $oldByName)) {
                $toDiff[$name] = $name;
                continue;
            }
            $differences[] = ToolAddedDiff::diff($newTool);
        }

        // 2. detect all removed versions.
        foreach ($oldByName as $oldTool) {
            if (array_key_exists($name = $oldTool->getName(), $newByName)) {
                $toDiff[$name] = $name;
                continue;
            }
            $differences[] = ToolRemovedDiff::diff($oldTool);
        }

        // 3. detect all changed versions.
        foreach ($toDiff as $diffTool) {
            if (null !== $diff = ToolChangedDiff::diff($oldByName[$diffTool], $newByName[$diffTool])) {
                $differences[] = $diff;
            }
        }

        return $differences;
    }
}
