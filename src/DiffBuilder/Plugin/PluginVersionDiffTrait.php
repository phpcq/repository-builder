<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\DiffBuilder\Plugin;

use Phpcq\RepositoryBuilder\DiffBuilder\VersionDiffTrait;
use Phpcq\RepositoryBuilder\Repository\Plugin\PhpFilePluginVersionInterface;
use Phpcq\RepositoryBuilder\Repository\Plugin\PluginVersionInterface;
use Phpcq\RepositoryBuilder\Repository\VersionRequirementList;

trait PluginVersionDiffTrait
{
    use VersionDiffTrait;

    private static function reqToStr(PluginVersionInterface $pluginVersion): string
    {
        $requirements = $pluginVersion->getRequirements();
        $result       = [];

        if (null !== $list = self::reqListToStr($requirements->getPhpRequirements())) {
            $result[] = 'platform: ' . $list;
        }
        if (null !== $list = self::reqListToStr($requirements->getToolRequirements())) {
            $result[] = 'tool: ' . $list;
        }
        if (null !== $list = self::reqListToStr($requirements->getPluginRequirements())) {
            $result[] = 'plugin: ' . $list;
        }
        if (null !== $list = self::reqListToStr($requirements->getComposerRequirements())) {
            $result[] = 'composer: ' . $list;
        }

        return implode(', ', $result);
    }

    private static function reqListToStr(VersionRequirementList $list): ?string
    {
        $result = [];
        foreach ($list as $requirement) {
            $result[] = $requirement->getName() . ':' . $requirement->getConstraint();
        }

        if ([] === $result) {
            return null;
        }

        return implode(', ', $result);
    }

    private static function hashToStr(PluginVersionInterface $pluginVersion): string
    {
        $hash = $pluginVersion->getHash();

        return $hash->getType() . ':' . $hash->getValue();
    }

    private static function getCodeFromVersion(PluginVersionInterface $version): string
    {
        if ($version instanceof PhpFilePluginVersionInterface) {
            return 'url:' . $version->getFilePath();
        }

        return 'md5:' . md5($version->getCode());
    }

    private static function getSignatureFromVersion(PluginVersionInterface $version): ?string
    {
        if ($version instanceof PhpFilePluginVersionInterface) {
            if (null === $signature = $version->getSignaturePath()) {
                return null;
            }
            return 'url:' . $signature;
        }

        if (null === $signature = $version->getSignature()) {
            return null;
        }

        return 'md5:' . md5($signature);
    }
}
