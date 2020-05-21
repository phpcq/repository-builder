<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\SourceProvider;

/**
 * This describes a source repository.
 */
interface SourceRepositoryInterface
{
    /**
     * Check if the repository must be refreshed from remote.
     */
    public function isFresh(): bool;

    /**
     * Refresh the repository with remote contents.
     */
    public function refresh(): void;
}
