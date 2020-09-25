<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\DiffBuilder;

trait VersionAddedDiffTrait
{
    use VersionDiffTrait;

    public function asString(string $prefix): string
    {
        return $prefix . 'Added version ' . $this->getVersion() . "\n";
    }
}
