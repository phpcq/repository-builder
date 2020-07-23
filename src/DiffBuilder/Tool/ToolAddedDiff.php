<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\DiffBuilder\Tool;

use Phpcq\RepositoryBuilder\DiffBuilder\ObjectAddedDiffTrait;
use Phpcq\RepositoryBuilder\DiffBuilder\ObjectDiffInterface;
use Phpcq\RepositoryBuilder\Repository\Tool\Tool;

final class ToolAddedDiff implements ObjectDiffInterface, ToolDiffInterface
{
    use ObjectAddedDiffTrait;

    public static function diff(Tool $new): ToolAddedDiff
    {
        // New tool, add all versions as new.
        $differences = [];
        foreach ($new->getIterator() as $version) {
            $differences[$version->getVersion()] = ToolVersionAddedDiff::diff($version);
        }

        return new static($new->getName(), $differences);
    }
}
