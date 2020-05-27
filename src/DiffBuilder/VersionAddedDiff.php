<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\DiffBuilder;

use Phpcq\RepositoryBuilder\Repository\ToolVersion;

final class VersionAddedDiff implements DiffInterface
{
    use VersionDiffTrait;

    public static function diff(ToolVersion $newVersion): VersionAddedDiff
    {
        return new static(
            $newVersion->getName(),
            $newVersion->getVersion(),
            [
                'phar-url'     => [null, $newVersion->getPharUrl()],
                'requirements' => [null, self::reqToStr($newVersion)],
                'hash'         => [null, self::hashToStr($newVersion)],
                'signature'    => [null, $newVersion->getSignatureUrl()],
                'bootstrap'    => [null, self::bootstrapToStr($newVersion)],
            ]
        );
    }

    public function asString(string $prefix): string
    {
        return $prefix . 'Added version ' . $this->version . "\n";
    }
}
