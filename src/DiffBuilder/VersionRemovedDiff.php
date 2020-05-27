<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\DiffBuilder;

use Phpcq\RepositoryBuilder\Repository\ToolVersion;

final class VersionRemovedDiff implements DiffInterface
{
    use VersionDiffTrait;

    public static function diff(ToolVersion $oldVersion): VersionRemovedDiff
    {
        return new static(
            $oldVersion->getName(),
            $oldVersion->getVersion(),
            [
                'phar-url'     => [$oldVersion->getPharUrl(), null],
                'requirements' => [self::reqToStr($oldVersion), null],
                'hash'         => [self::hashToStr($oldVersion), null],
                'signature'    => [$oldVersion->getSignatureUrl(), null],
                'bootstrap'    => [self::bootstrapToStr($oldVersion), null],
            ]
        );
    }

    public function asString(string $prefix): string
    {
        return $prefix . 'Removed version ' . $this->version . "\n";
    }
}
