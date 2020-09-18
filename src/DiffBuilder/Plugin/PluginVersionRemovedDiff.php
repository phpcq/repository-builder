<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\DiffBuilder\Plugin;

use Phpcq\RepositoryBuilder\DiffBuilder\ObjectVersionRemovedDiffInterface;
use Phpcq\RepositoryBuilder\DiffBuilder\PropertyDifference;
use Phpcq\RepositoryBuilder\DiffBuilder\VersionRemovedDiffTrait;
use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;

final class PluginVersionRemovedDiff implements ObjectVersionRemovedDiffInterface, PluginDiffInterface
{
    use VersionRemovedDiffTrait;
    use PluginVersionDiffTrait;

    public static function diff(PluginVersionInterface $oldVersion): PluginVersionRemovedDiff
    {
        /** @psalm-var list<PropertyDifference> $differences */
        $differences = [
            PropertyDifference::removed('api-version', $oldVersion->getApiVersion()),
            PropertyDifference::removed('code', self::getCodeFromVersion($oldVersion)),
            PropertyDifference::removed('requirements', self::reqToStr($oldVersion)),
            PropertyDifference::removed('checksum', self::hashToStr($oldVersion)),
            PropertyDifference::removed('signature', self::getSignatureFromVersion($oldVersion)),
        ];
        
        return new static($oldVersion->getName(), $oldVersion->getVersion(), $differences);
    }
}
