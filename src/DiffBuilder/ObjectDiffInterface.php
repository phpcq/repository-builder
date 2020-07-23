<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\DiffBuilder;

interface ObjectDiffInterface extends DiffInterface
{
    /**
     * Obtain the name of the instance this diff relates to.
     *
     * @return string
     */
    public function getName(): string;

    /** @psalm-return array<string, ObjectVersionDiffInterface> */
    public function getDifferences(): array;

    /**
     * Render the diff as string (\n is used for line breaks).
     *
     * @param string $prefix The prefix per line.
     *
     * @return string
     */
    public function asString(string $prefix): string;
}
