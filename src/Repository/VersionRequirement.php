<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Repository;

class VersionRequirement
{
    private string $name;

    private string $constraint;

    /**
     * Create a new instance.
     *
     * @param string $name
     * @param string $constraint
     */
    public function __construct(string $name, string $constraint = '*')
    {
        $this->name       = $name;
        $this->constraint = $constraint;
    }

    /**
     * Retrieve name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Retrieve constraint.
     *
     * @return string
     */
    public function getConstraint(): string
    {
        return $this->constraint;
    }
}
