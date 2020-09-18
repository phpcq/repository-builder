<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\DiffBuilder\Tool;

use Phpcq\RepositoryBuilder\DiffBuilder\ObjectVersionAddedDiffInterface;
use Phpcq\RepositoryBuilder\DiffBuilder\PropertyDifference;
use Phpcq\RepositoryBuilder\DiffBuilder\VersionAddedDiffTrait;
use Phpcq\RepositoryDefinition\Tool\ToolVersion;

final class ToolVersionAddedDiff implements ObjectVersionAddedDiffInterface, ToolDiffInterface
{
    use VersionAddedDiffTrait;
    use ToolVersionDiffTrait;

    public static function diff(ToolVersion $newVersion): ToolVersionAddedDiff
    {
        return new static(
            $newVersion->getName(),
            $newVersion->getVersion(),
            [
                PropertyDifference::added('phar-url', $newVersion->getPharUrl()),
                PropertyDifference::added('requirements', self::reqToStr($newVersion)),
                PropertyDifference::added('checksum', self::hashToStr($newVersion)),
                PropertyDifference::added('signature', $newVersion->getSignatureUrl()),
            ]
        );
    }
}
