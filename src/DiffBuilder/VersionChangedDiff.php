<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\DiffBuilder;

use Phpcq\RepositoryBuilder\Repository\ToolVersion;

final class VersionChangedDiff implements DiffInterface
{
    use VersionDiffTrait;

    public static function diff(ToolVersion $oldVersion, ToolVersion $newVersion): ?VersionChangedDiff
    {
        // ensure both versions are same tool version.
        assert($oldVersion->getName() === $newVersion->getName());
        assert($oldVersion->getVersion() === $newVersion->getVersion());
        $differences = [];
        if (($oldValue = $oldVersion->getPharUrl()) !== ($newValue = $newVersion->getPharUrl())) {
            $differences['phar-url'] = [$oldValue, $newValue];
        }

        if (($oldValue = self::reqToStr($oldVersion)) !== ($newValue = self::reqToStr($newVersion))) {
            $differences['requirements'] = [$oldValue, $newValue];
        }

        if (($oldValue = self::hashToStr($oldVersion)) !== ($newValue = self::hashToStr($newVersion))) {
            $differences['hash'] = [$oldValue, $newValue];
        }

        if (($oldValue = $oldVersion->getSignatureUrl()) !== ($newValue = $newVersion->getSignatureUrl())) {
            $differences['signature'] = [$oldValue, $newValue];
        }

        if (($oldValue = self::bootstrapToStr($oldVersion)) !== ($newValue = self::bootstrapToStr($newVersion))) {
            $differences['bootstrap'] = [$oldValue, $newValue];
        }

        if (
            ($oldValue = self::bootstrapHashToStr($oldVersion)) !==
            ($newValue = self::bootstrapHashToStr($newVersion))
        ) {
            $differences['bootstrap-hash'] = [$oldValue, $newValue];
        }

        if (empty($differences)) {
            return null;
        }

        return new static($oldVersion->getName(), $oldVersion->getVersion(), $differences);
    }

    public function asString(string $prefix): string
    {
        $result = [$prefix . 'Changed version ' . $this->version . ':'];
        foreach ($this->differences as $difference => $values) {
            $result[] = $prefix . '  ' . $difference . ':';
            $result[] = $prefix . '  ' . '  - ' . $values[0];
            $result[] = $prefix . '  ' . '  + ' . $values[1];
        }
        $result[] = '';

        return implode("\n", $result);
    }
}
