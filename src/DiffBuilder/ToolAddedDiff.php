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
        $differences = [];
        foreach ($new->getIterator() as $version) {
            $differences[$version->getVersion()] = VersionAddedDiff::diff($version);
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

        return $prefix . 'Added ' . $this->toolName . ':' . "\n" . implode('', $result);
    }
}
