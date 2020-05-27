<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\DiffBuilder;

interface DiffInterface
{
    /**
     * Render the diff as string (\n is used for line breaks).
     *
     * @param string $prefix The prefix per line.
     *
     * @return string
     */
    public function asString(string $prefix): string;
}
