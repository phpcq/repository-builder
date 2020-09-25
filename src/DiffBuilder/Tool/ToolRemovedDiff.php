<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\DiffBuilder\Tool;

use Phpcq\RepositoryBuilder\DiffBuilder\ObjectDiffInterface;
use Phpcq\RepositoryBuilder\DiffBuilder\ObjectRemovedDiffTrait;
use Phpcq\RepositoryDefinition\Tool\Tool;

final class ToolRemovedDiff implements ObjectDiffInterface, ToolDiffInterface
{
    use ObjectRemovedDiffTrait;

    public static function diff(Tool $new): ToolRemovedDiff
    {
        // New tool, add all versions as new.
        $differences = [];
        foreach ($new->getIterator() as $version) {
            $differences[$version->getVersion()] = ToolVersionRemovedDiff::diff($version);
        }

        return new static($new->getName(), $differences);
    }
}
