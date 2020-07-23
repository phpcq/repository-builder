<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\DiffBuilder\Plugin;

use Phpcq\RepositoryBuilder\DiffBuilder\ObjectVersionAddedDiffInterface;
use Phpcq\RepositoryBuilder\DiffBuilder\PropertyDifference;
use Phpcq\RepositoryBuilder\DiffBuilder\VersionAddedDiffTrait;
use Phpcq\RepositoryBuilder\Repository\Plugin\PluginVersionInterface;

final class PluginVersionAddedDiff implements ObjectVersionAddedDiffInterface, PluginDiffInterface
{
    use VersionAddedDiffTrait;
    use PluginVersionDiffTrait;

    public static function diff(PluginVersionInterface $newVersion): PluginVersionAddedDiff
    {
        return new self(
            $newVersion->getName(),
            $newVersion->getVersion(),
            [
                PropertyDifference::added('api-version', $newVersion->getApiVersion()),
                PropertyDifference::added('code', self::getCodeFromVersion($newVersion)),
                PropertyDifference::added('requirements', self::reqToStr($newVersion)),
                PropertyDifference::added('checksum', self::hashToStr($newVersion)),
                PropertyDifference::added('signature', self::getSignatureFromVersion($newVersion)),
            ]
        );
    }
}
