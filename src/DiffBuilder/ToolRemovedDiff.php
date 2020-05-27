<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\DiffBuilder;

use Phpcq\RepositoryBuilder\Repository\Tool;

final class ToolRemovedDiff implements DiffInterface
{
    use ToolDiffTrait;

    public static function diff(Tool $new): ToolRemovedDiff
    {
        // New tool, add all versions as new.
        $differences = [];
        foreach ($new->getIterator() as $version) {
            $differences[$version->getVersion()] = VersionRemovedDiff::diff($version);
        }

        return new static($new->getName(), $differences);
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

        return $prefix . 'Removed ' . $this->toolName . ':' . "\n" . implode('', $result);
    }
}
