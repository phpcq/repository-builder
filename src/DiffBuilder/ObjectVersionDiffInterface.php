<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\DiffBuilder;

interface ObjectVersionDiffInterface extends DiffInterface
{
    /**
     * Obtain the name of the instance this diff relates to.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Obtain the list of changes in the object version.
     *
     * Result is a list of arrays indexed by property name.
     *
     * Array key `0` represents the old value and key `1` represents the new value.
     *
     * @psalm-return list<PropertyDifference>
     */
    public function getDifferences(): array;

    /**
     * Obtain the version this relates to.
     *
     * @return string
     */
    public function getVersion(): string;
}
