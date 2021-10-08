<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\SourceProvider\Tool;

use InvalidArgumentException;
use Phpcq\RepositoryBuilder\SourceProvider\SourceRepositoryInterface;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;

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
     * @param ToolVersionInterface $version The version to test.
     *
     * @return bool
     */
    public function supports(ToolVersionInterface $version): bool;

    /**
     * Enrich the passed version.
     *
     * @param ToolVersionInterface $version The version to enrich
     *
     * @return void
     *
     * @throws InvalidArgumentException When the passed version is not supported.
     */
    public function enrich(ToolVersionInterface $version): void;
}
