<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\DiffBuilder\Plugin;

use Phpcq\RepositoryBuilder\DiffBuilder\ObjectVersionChangedDiffInterface;
use Phpcq\RepositoryBuilder\DiffBuilder\PropertyDifference;
use Phpcq\RepositoryBuilder\DiffBuilder\VersionChangedDiffTrait;
use Phpcq\RepositoryBuilder\Repository\Plugin\PluginVersionInterface;

final class PluginVersionChangedDiff implements ObjectVersionChangedDiffInterface, PluginDiffInterface
{
    use VersionChangedDiffTrait;
    use PluginVersionDiffTrait;

    public static function diff(
        PluginVersionInterface $oldVersion,
        PluginVersionInterface $newVersion
    ): ?PluginVersionChangedDiff {
        // ensure both versions are for same plugin and version.
        assert($oldVersion->getName() === $newVersion->getName());
        assert($oldVersion->getVersion() === $newVersion->getVersion());
        $differences = [];

        if (($oldValue = $oldVersion->getApiVersion()) !== ($newValue = $newVersion->getApiVersion())) {
            $differences[] = PropertyDifference::changed('api-version', $oldValue, $newValue);
        }

        if (null !== $diff = self::diffCode($oldVersion, $newVersion)) {
            $differences[] = $diff;
        }

        if (($oldValue = self::reqToStr($oldVersion)) !== ($newValue = self::reqToStr($newVersion))) {
            $differences[] = PropertyDifference::changed('requirements', $oldValue, $newValue);
        }

        if (($oldValue = self::hashToStr($oldVersion)) !== ($newValue = self::hashToStr($newVersion))) {
            $differences[] = PropertyDifference::changed('checksum', $oldValue, $newValue);
        }

        if (null !== $diff = self::diffSignature($oldVersion, $newVersion)) {
            $differences[] = $diff;
        }

        if (empty($differences)) {
            return null;
        }

        return new static($oldVersion->getName(), $oldVersion->getVersion(), $differences);
    }

    private static function diffCode(PluginVersionInterface $old, PluginVersionInterface $new): ?PropertyDifference
    {
        $oldValue = self::getCodeFromVersion($old);
        $newValue = self::getCodeFromVersion($new);
        if ($oldValue !== $newValue) {
            return PropertyDifference::changed('code', $oldValue, $newValue);
        }

        return null;
    }

    private static function diffSignature(PluginVersionInterface $old, PluginVersionInterface $new): ?PropertyDifference
    {
        $oldValue = self::getSignatureFromVersion($old);
        $newValue = self::getSignatureFromVersion($new);
        if ($oldValue !== $newValue) {
            return PropertyDifference::changed('signature', $oldValue, $newValue);
        }

        return null;
    }
}
