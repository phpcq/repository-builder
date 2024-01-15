<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\DiffBuilder;

use Phpcq\RepositoryBuilder\DiffBuilder\Plugin\PluginAddedDiff;
use Phpcq\RepositoryBuilder\DiffBuilder\Plugin\PluginDiff;
use Phpcq\RepositoryBuilder\DiffBuilder\Plugin\PluginDiffInterface;
use Phpcq\RepositoryBuilder\DiffBuilder\Plugin\PluginRemovedDiff;
use Phpcq\RepositoryBuilder\DiffBuilder\Tool\ToolAddedDiff;
use Phpcq\RepositoryBuilder\DiffBuilder\Tool\ToolDiff;
use Phpcq\RepositoryBuilder\DiffBuilder\Tool\ToolDiffInterface;
use Phpcq\RepositoryBuilder\DiffBuilder\Tool\ToolRemovedDiff;
use Phpcq\RepositoryDefinition\Plugin\Plugin;
use Phpcq\RepositoryDefinition\Tool\Tool;
use UnexpectedValueException;

final class Diff implements DiffInterface
{
    /**
     * @var ObjectDiffInterface[]
     * @psalm-var list<ObjectDiffInterface>
     */
    private array $differences;

    /**
     * @param Plugin[] $newPlugins Plugins indexed by name.
     * @param Tool[]   $newTools Tools indexed by name.
     *
     * @psalm-param list<Plugin> $newPlugins
     * @psalm-param list<Tool> $newTools
     */
    public static function created(array $newPlugins, array $newTools): ?self
    {
        if (empty($newTools) && empty($newPlugins)) {
            return null;
        }

        return self::diff([], $newPlugins, [], $newTools);
    }

    /**
     * @param Plugin[] $oldPlugins Plugins indexed by name.
     * @param Tool[] $oldTools     Tools indexed by name.
     *
     * @psalm-param list<Plugin> $oldPlugins
     * @psalm-param list<Tool> $oldTools
     */
    public static function removed(array $oldPlugins, array $oldTools): ?self
    {
        if (empty($oldPlugins) && empty($oldTools)) {
            return null;
        }

        return self::diff($oldPlugins, [], $oldTools, []);
    }

    /**
     * @param Plugin[] $oldPlugins Plugins indexed by name.
     * @param Plugin[] $newPlugins Plugins indexed by name.
     * @param Tool[]   $oldTools Tools indexed by tool name.
     * @param Tool[]   $newTools Tools indexed by tool name.
     *
     * @psalm-param list<Plugin> $oldPlugins
     * @psalm-param list<Plugin> $newPlugins
     * @psalm-param list<Tool> $oldTools
     * @psalm-param list<Tool> $newTools
     */
    public static function diff(array $oldPlugins, array $newPlugins, array $oldTools, array $newTools): ?self
    {
        $differences = array_merge(PluginDiff::diff($oldPlugins, $newPlugins), ToolDiff::diff($oldTools, $newTools));
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
        $byType = $this->differencesByTypeAsString($prefix);
        $result = [];
        foreach ($byType as $typeName => $values) {
            if (empty($values)) {
                continue;
            }
            $result[] = ['  Changed ' . $typeName . 's:' . "\n", ...$values];
        }

        return implode(
            '',
            array_merge(
                [
                    $prefix . $this->asSummary() . "\n" .
                    $prefix . "\n" .
                    $prefix . 'Changes in repository:' . "\n"
                ],
                ...$result
            )
        );
    }

    public function asSummary(): string
    {
        // 1. One tool has changed
        if (1 === count($this->differences)) {
            $difference = reset($this->differences);
            return $this->asSummaryForSingleObject($difference);
        }

        // 2. Multiple changes
        $names = [];
        foreach ($this->differences as $difference) {
            $names[] = $difference->getName();
        }

        // 2.1. Up to 3 tools changed: 'Update versions of "tool-name", "tool-name-2", "tool-name-3"'
        if (3 >= count($names)) {
            return 'Update versions of "' . implode('", "', $names) . '"';
        }

        // 2.2. More than 3 tools changed: 'Update versions of "tool-name", "tool-name-2" and 2 more tools'
        return 'Update versions of "' . implode('", "', array_slice($names, 0, 2)) . '" and ' .
            (count($names) - 2) . ' more';
    }

    /** @psalm-param list<ObjectDiffInterface> $differences */
    private function __construct(array $differences)
    {
        $this->differences = $differences;
        usort(
            $this->differences,
            function (ObjectDiffInterface $objA, ObjectDiffInterface $objB) {
                return $objA->getName() <=> $objB->getName();
            }
        );
    }

    /** @psalm-return array{tool: list<string>, plugin: list<string>} */
    protected function differencesByTypeAsString(string $prefix): array
    {
        /** @psalm-var array{tool: list<string>, plugin: list<string>} $byType */
        $byType = [
            'plugin' => [],
            'tool'   => [],
        ];
        foreach ($this->differences as $difference) {
            $byType[$this->getSlugFromDiffType($difference)][] = $difference->asString($prefix . '    ');
        }

        return $byType;
    }

    /**
     * @param ObjectDiffInterface $toolDiff
     */
    private function asSummaryForSingleObject(ObjectDiffInterface $toolDiff): string
    {
        // Easy out if tool is added or removed.
        if (!$toolDiff instanceof ObjectChangedDiffInterface) {
            return $this->asSummaryForSingleObjectAddOrRemove($toolDiff);
        }

        $toolDifferences = $toolDiff->getDifferences();
        if (1 === count($toolDifferences)) {
            return $this->asSummaryForSingleObjectSingleDifference(reset($toolDifferences));
        }

        return $this->asSummaryForSingleObjectMultipleDifferences($toolDiff, $toolDifferences);
    }

    /**
     * @param ObjectDiffInterface $diff
     */
    private function asSummaryForSingleObjectAddOrRemove(ObjectDiffInterface $diff): string
    {
        switch (true) {
            case $diff instanceof ToolAddedDiff:
                return sprintf('Add tool "%1$s"', $diff->getName());
            case $diff instanceof ToolRemovedDiff:
                return sprintf('Remove tool "%1$s"', $diff->getName());
            case $diff instanceof PluginAddedDiff:
                return sprintf('Add plugin "%1$s"', $diff->getName());
            case $diff instanceof PluginRemovedDiff:
                return sprintf('Remove plugin "%1$s"', $diff->getName());
            default:
        }
        // Can never happen.
        // @codeCoverageIgnoreStart
        throw new UnexpectedValueException('Unknown diff class: ' . get_class($diff));
        // @codeCoverageIgnoreEnd
    }

    private function asSummaryForSingleObjectSingleDifference(ObjectVersionDiffInterface $diff): string
    {
        $type    = $this->getSlugFromDiffType($diff);
        $name    = $diff->getName();
        $version = $diff->getVersion();
        switch (true) {
            // 1. Only one version has been added: 'Add version x.y.z of tool "tool-name"'
            case $diff instanceof ObjectVersionAddedDiffInterface:
                return sprintf('Add version %2$s of %1$s "%3$s"', $type, $version, $name);
            // 2. Only one version has been removed: 'Remove version x.y.z of tool "tool-name"'
            case $diff instanceof ObjectVersionRemovedDiffInterface:
                return sprintf('Remove version %2$s of %1$s "%3$s"', $type, $version, $name);
            // 3. Only one version has Changed: 'Update version x.y.z of tool "tool-name"'
            case $diff instanceof ObjectVersionChangedDiffInterface:
                return sprintf('Update version %2$s of %1$s "%3$s"', $type, $version, $name);
            default:
        }

        // Can never happen.
        // @codeCoverageIgnoreStart
        throw new UnexpectedValueException('Unknown diff class: ' . get_class($diff));
        // @codeCoverageIgnoreEnd
    }

    /**
     * @param ObjectVersionDiffInterface[] $differences
     */
    private function asSummaryForSingleObjectMultipleDifferences(ObjectDiffInterface $tool, array $differences): string
    {
        // Multiple changes happened: 'Update tool "tool-name": 3 new versions, 1 deleted, 2 changed'
        $add = 0;
        $del = 0;
        $chg = 0;
        foreach ($differences as $toolDifference) {
            switch (true) {
                case $toolDifference instanceof ObjectVersionAddedDiffInterface:
                    $add++;
                    break;
                case $toolDifference instanceof ObjectVersionRemovedDiffInterface:
                    $del++;
                    break;
                case $toolDifference instanceof ObjectVersionChangedDiffInterface:
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
            $tool->getName(),
            implode(', ', $actions)
        );
    }

    /** @psalm-return 'plugin'|'tool' */
    private function getSlugFromDiffType(DiffInterface $difference): string
    {
        switch (true) {
            case $difference instanceof PluginDiffInterface:
                return 'plugin';
            case $difference instanceof ToolDiffInterface:
                return 'tool';
            default:
        }

        // Can never happen.
        // @codeCoverageIgnoreStart
        throw new UnexpectedValueException('Unknown diff type: ' . implode(',', class_implements($difference)));
        // @codeCoverageIgnoreEnd
    }
}
