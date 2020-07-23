<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\DiffBuilder\Plugin;

use Phpcq\RepositoryBuilder\DiffBuilder\ObjectAddedDiffTrait;
use Phpcq\RepositoryBuilder\DiffBuilder\ObjectDiffInterface;
use Phpcq\RepositoryBuilder\Repository\Plugin\Plugin;

final class PluginAddedDiff implements ObjectDiffInterface, PluginDiffInterface
{
    use ObjectAddedDiffTrait;

    public static function diff(Plugin $new): PluginAddedDiff
    {
        // New tool, add all versions as new.
        $differences = [];
        foreach ($new->getIterator() as $version) {
            $differences[$version->getVersion()] = PluginVersionAddedDiff::diff($version);
        }

        return new static($new->getName(), $differences);
    }
}
