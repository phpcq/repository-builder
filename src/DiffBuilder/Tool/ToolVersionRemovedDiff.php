<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\DiffBuilder\Tool;

use Phpcq\RepositoryBuilder\DiffBuilder\ObjectVersionRemovedDiffInterface;
use Phpcq\RepositoryBuilder\DiffBuilder\PropertyDifference;
use Phpcq\RepositoryBuilder\DiffBuilder\VersionRemovedDiffTrait;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;

final class ToolVersionRemovedDiff implements ObjectVersionRemovedDiffInterface, ToolDiffInterface
{
    use VersionRemovedDiffTrait;
    use ToolVersionDiffTrait;

    public static function diff(ToolVersionInterface $oldVersion): ToolVersionRemovedDiff
    {
        return new static(
            $oldVersion->getName(),
            $oldVersion->getVersion(),
            [
                PropertyDifference::removed('phar-url', $oldVersion->getPharUrl()),
                PropertyDifference::removed('requirements', self::reqToStr($oldVersion)),
                PropertyDifference::removed('checksum', self::hashToStr($oldVersion)),
                PropertyDifference::removed('signature', $oldVersion->getSignatureUrl()),
            ]
        );
    }
}
