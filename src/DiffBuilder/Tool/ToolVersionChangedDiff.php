<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\DiffBuilder\Tool;

use Phpcq\RepositoryBuilder\DiffBuilder\ObjectVersionChangedDiffInterface;
use Phpcq\RepositoryBuilder\DiffBuilder\PropertyDifference;
use Phpcq\RepositoryBuilder\DiffBuilder\VersionChangedDiffTrait;
use Phpcq\RepositoryBuilder\Repository\Tool\ToolVersion;

final class ToolVersionChangedDiff implements ObjectVersionChangedDiffInterface, ToolDiffInterface
{
    use VersionChangedDiffTrait;
    use ToolVersionDiffTrait;

    public static function diff(ToolVersion $oldVersion, ToolVersion $newVersion): ?ToolVersionChangedDiff
    {
        // ensure both versions are same tool version.
        assert($oldVersion->getName() === $newVersion->getName());
        assert($oldVersion->getVersion() === $newVersion->getVersion());
        $differences = [];
        if (($oldValue = $oldVersion->getPharUrl()) !== ($newValue = $newVersion->getPharUrl())) {
            $differences[] = PropertyDifference::changed('url', $oldValue, $newValue);
        }

        if (($oldValue = self::reqToStr($oldVersion)) !== ($newValue = self::reqToStr($newVersion))) {
            $differences[] = PropertyDifference::changed('requirements', $oldValue, $newValue);
        }

        if (($oldValue = self::hashToStr($oldVersion)) !== ($newValue = self::hashToStr($newVersion))) {
            $differences[] = PropertyDifference::changed('checksum', $oldValue, $newValue);
        }

        if (($oldValue = $oldVersion->getSignatureUrl()) !== ($newValue = $newVersion->getSignatureUrl())) {
            $differences[] = PropertyDifference::changed('signature', $oldValue, $newValue);
        }

        if (empty($differences)) {
            return null;
        }

        return new static($oldVersion->getName(), $oldVersion->getVersion(), $differences);
    }
}
