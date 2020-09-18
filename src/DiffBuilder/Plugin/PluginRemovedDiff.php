<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\DiffBuilder\Plugin;

use Phpcq\RepositoryBuilder\DiffBuilder\ObjectDiffInterface;
use Phpcq\RepositoryBuilder\DiffBuilder\ObjectRemovedDiffTrait;
use Phpcq\RepositoryDefinition\Plugin\Plugin;

final class PluginRemovedDiff implements ObjectDiffInterface, PluginDiffInterface
{
    use ObjectRemovedDiffTrait;

    public static function diff(Plugin $new): PluginRemovedDiff
    {
        // New tool, add all versions as new.
        $differences = [];
        foreach ($new->getIterator() as $version) {
            $differences[$version->getVersion()] = PluginVersionRemovedDiff::diff($version);
        }

        return new static($new->getName(), $differences);
    }
}
