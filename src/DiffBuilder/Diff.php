<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\DiffBuilder;

use Phpcq\RepositoryBuilder\Repository\Tool;
use UnexpectedValueException;

final class Diff implements DiffInterface
{
    /**
     * @var DiffInterface[]
     */
    private array $differences;

    /**
     * @param Tool[] $new Tools indexed by tool name.
     *
     * @return Diff|null
     */
    public static function created(array $new): ?Diff
    {
        if (empty($new)) {
            return null;
        }
        $differences = [];
        foreach ($new as $tool) {
            $differences[$tool->getName()] = ToolAddedDiff::diff($tool);
        }
        return new Diff($differences);
    }

    /**
     * @param Tool[] $old Tools indexed by tool name.
     *
     * @return Diff|null
     */
    public static function removed(array $old): ?Diff
    {
        if (empty($old)) {
            return null;
        }
        $differences = [];
        foreach ($old as $tool) {
            $differences[$tool->getName()] = ToolRemovedDiff::diff($tool);
        }

        return new Diff($differences);
    }

    /**
     * @param Tool[] $old Tools indexed by tool name.
     * @param Tool[] $new Tools indexed by tool name.
     *
     * @return Diff|null
     */
    public static function diff(array $old, array $new): ?Diff
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
            if (null !== $diff = ToolChangedDiff::diff($old[$diffTool], $new[$diffTool])) {
                $differences[$diffTool] = $diff;
            }
        }

        if (empty($differences)) {
            return null;
        }

        return new self($differences);
    }

    public function __toString(): string
    {
        return $this->asString('');
    }

    public function asString(string $prefix): string
    {
        $result = [
            $prefix . $this->asSummary() . "\n" .
            $prefix . "\n" .
            $prefix . 'Changes in repository:' . "\n"
        ];
        foreach ($this->differences as $difference) {
            $result[] = $difference->asString($prefix . '  ');
        }

        return implode('', $result);
    }

    public function asSummary(): string
    {
        // 1. One tool has changed
        if (1 === count($this->differences)) {
            /** @var ToolAddedDiff|ToolRemovedDiff|ToolChangedDiff $difference */
            $difference = reset($this->differences);
            return $this->asSummaryForSingleTool($difference);
        }

        // 2. Multiple tools have changed
        $toolNames = [];
        foreach ($this->differences as $difference) {
            /** @var ToolAddedDiff|ToolRemovedDiff|ToolChangedDiff $difference */
            $toolNames[] = $difference->getToolName();
        }
        // 2.1. Up to 3 tools changed: 'Update versions of "tool-name", "tool-name-2", "tool-name-3"'
        if (3 >= count($toolNames)) {
            return 'Update versions of "' . implode('", "', $toolNames) . '"';
        }

        // 2.2. More than 3 tools changed: 'Update versions of "tool-name", "tool-name-2" and 2 more tools'
        return 'Update versions of "' . implode('", "', array_slice($toolNames, 0, 2)) . '" and ' .
            (count($toolNames) - 2) . ' more tools';
    }

    private function __construct(array $differences)
    {
        $this->differences = $differences;
        ksort($this->differences);
    }

    /**
     * @param ToolAddedDiff|ToolRemovedDiff|ToolChangedDiff $toolDiff
     */
    private function asSummaryForSingleTool(DiffInterface $toolDiff): string
    {
        // Easy out if tool is added or removed.
        if (!$toolDiff instanceof ToolChangedDiff) {
            return $this->asSummaryForSingleToolAddOrRemove($toolDiff);
        }

        $toolDifferences = $toolDiff->getDifferences();
        if (1 === count($toolDifferences)) {
            return $this->asSummaryForSingleToolSingleDifference(reset($toolDifferences));
        }

        return $this->asSummaryForSingleToolMultipleDifferences($toolDiff, $toolDifferences);
    }

    /**
     * @param ToolAddedDiff|ToolRemovedDiff $diff
     */
    private function asSummaryForSingleToolAddOrRemove(DiffInterface $diff): string
    {
        switch (true) {
            case $diff instanceof ToolAddedDiff:
                return sprintf('Add tool "%1$s"', $diff->getToolName());
            case $diff instanceof ToolRemovedDiff:
                return sprintf('Remove tool "%1$s"', $diff->getToolName());
            default:
        }
        // Can never happen.
        // @codeCoverageIgnoreStart
        throw new UnexpectedValueException('Unknown diff class: ' . get_class($diff));
        // @codeCoverageIgnoreEnd
    }

    /**
     * @param VersionAddedDiff|VersionRemovedDiff|VersionChangedDiff $diff
     */
    private function asSummaryForSingleToolSingleDifference(DiffInterface $diff): string
    {
        switch (true) {
            // 1. Only one version has been added: 'Add version x.y.z of tool "tool-name"'
            case $diff instanceof VersionAddedDiff:
                return sprintf('Add version %1$s of tool "%2$s"', $diff->getVersion(), $diff->getToolName());
            // 2. Only one version has been removed: 'Remove version x.y.z of tool "tool-name"'
            case $diff instanceof VersionRemovedDiff:
                return sprintf('Remove version %1$s of tool "%2$s"', $diff->getVersion(), $diff->getToolName());
            // 3. Only one version has Changed: 'Update version x.y.z of tool "tool-name"'
            case $diff instanceof VersionChangedDiff:
                return sprintf('Update version %1$s of tool "%2$s"', $diff->getVersion(), $diff->getToolName());
            default:
        }
        // Can never happen.
        // @codeCoverageIgnoreStart
        throw new UnexpectedValueException('Unknown diff class: ' . get_class($diff));
        // @codeCoverageIgnoreEnd
    }

    /**
     * @param VersionAddedDiff[]|VersionRemovedDiff[]|VersionChangedDiff[] $differences
     */
    private function asSummaryForSingleToolMultipleDifferences(ToolChangedDiff $tool, array $differences): string
    {
        // Multiple changes happened: 'Update tool "tool-name": 3 new versions, 1 deleted, 2 changed'
        $add = 0;
        $del = 0;
        $chg = 0;
        foreach ($differences as $toolDifference) {
            switch (true) {
                case $toolDifference instanceof VersionAddedDiff:
                    $add++;
                    break;
                case $toolDifference instanceof VersionRemovedDiff:
                    $del++;
                    break;
                case $toolDifference instanceof VersionChangedDiff:
                    $chg++;
                    break;
                default:
                    // Can never happen.
                    // @codeCoverageIgnoreStart
                    throw new UnexpectedValueException('Unknown diff class: ' . get_class($toolDifference));
                    // @codeCoverageIgnoreEnd
            }
        }

        $actions = [];
        if (0 < $add) {
            $actions[] = $add . ' new versions';
        }
        if (0 < $del) {
            $actions[] = $del . ' versions deleted';
        }
        if (0 < $chg) {
            $actions[] = $chg . ' versions changed';
        }

        return sprintf(
            'Update tool "%1$s": %2$s',
            $tool->getToolName(),
            implode(', ', $actions)
        );
    }
}
