<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\DiffBuilder;

trait VersionRemovedDiffTrait
{
    use VersionDiffTrait;

    public function asString(string $prefix): string
    {
        return $prefix . 'Removed version ' . $this->getVersion() . "\n";
    }
}
