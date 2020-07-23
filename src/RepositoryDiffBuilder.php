<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder;

use LogicException;
use Phpcq\RepositoryBuilder\DiffBuilder\Diff;
use Phpcq\RepositoryBuilder\Repository\RepositoryLoader;

final class RepositoryDiffBuilder
{
    private string $baseDir;

    /**
     * @psalm-var array{
     *   tools: list<\Phpcq\RepositoryBuilder\Repository\Tool\Tool>,
     *   plugins: list<\Phpcq\RepositoryBuilder\Repository\Plugin\Plugin>
     * }|null
     */
    private ?array $oldData;

    public function __construct(string $baseDir)
    {
        $this->baseDir = $baseDir;
        $this->oldData = RepositoryLoader::load($this->baseDir);
    }

    public function generate(): ?Diff
    {
        $newData = RepositoryLoader::load($this->baseDir);

        if (null === $this->oldData && null === $newData) {
            throw new LogicException('new value and old value must not both be null.');
        }

        // New repository, add all tools as new.
        if (null === $this->oldData) {
            return Diff::created($newData['plugins'], $newData['tools']);
        }

        // Repository got removed, add all versions as removed.
        if (null === $newData) {
            return Diff::removed($this->oldData['plugins'], $this->oldData['tools']);
        }

        return Diff::diff($this->oldData['plugins'], $newData['plugins'], $this->oldData['tools'], $newData['tools']);
    }
}
