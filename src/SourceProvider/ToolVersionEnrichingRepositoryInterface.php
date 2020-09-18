<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\SourceProvider;

use InvalidArgumentException;
use Phpcq\RepositoryDefinition\Tool\ToolVersion;

/**
 * This describes an enriching source repository.
 *
 * These repositories do not spawn versions on their own but "enrich" versions with additional meta data.
 */
interface ToolVersionEnrichingRepositoryInterface extends SourceRepositoryInterface
{
    /**
     * Test if a given version is supported.
     *
     * @param ToolVersion $version The version to test.
     *
     * @return bool
     */
    public function supports(ToolVersion $version): bool;

    /**
     * Enrich the passed version.
     *
     * @param ToolVersion $version The version to enrich
     *
     * @return void
     *
     * @throws InvalidArgumentException When the passed version is not supported.
     */
    public function enrich(ToolVersion $version): void;
}
