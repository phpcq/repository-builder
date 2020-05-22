<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\DiffBuilder;

use Phpcq\RepositoryBuilder\Repository\Tool;

final class ToolAddedDiff implements DiffInterface
{
    use ToolDiffTrait;

    public static function diff(Tool $new): ToolAddedDiff
    {
        // New tool, add all versions as new.
        $changes = [];
        foreach ($new->getIterator() as $version) {
            $changes[$version->getVersion()] = VersionAddedDiff::diff($version);
        }

        return new static($new->getName(), $changes);
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

        return $prefix . 'Added ' . $this->toolName . ':' . "\n" . implode('', $result);
    }
}
