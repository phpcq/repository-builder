<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Repository;

use Generator;
use IteratorAggregate;
use LogicException;
use Traversable;

/**
 * @template-implements IteratorAggregate<int, VersionRequirement>
 */
class VersionRequirementList implements IteratorAggregate
{
    /**
     * @var VersionRequirement[]
     */
    private array $requirements = [];

    /**
     * Create a new instance.
     *
     * @param VersionRequirement[] $requirements
     */
    public function __construct(array $requirements = [])
    {
        foreach ($requirements as $requirement) {
            $this->add($requirement);
        }
    }

    public function add(VersionRequirement $requirement): void
    {
        $name = $requirement->getName();
        if ($this->has($name) && $this->get($name)->getConstraint() !== $requirement->getConstraint()) {
            throw new LogicException('Requirement already added for ' . $name);
        }

        $this->requirements[$name] = $requirement;
    }

    public function get(string $name): VersionRequirement
    {
        if (!$this->has($name)) {
            throw new LogicException('Requirement not added for ' . $name);
        }

        return $this->requirements[$name];
    }

    public function has(string $name): bool
    {
        return isset($this->requirements[$name]);
    }

    /**
     * @return Generator|Traversable|VersionRequirement[]
     * @psalm-return Generator<VersionRequirement>
     */
    public function getIterator()
    {
        foreach ($this->requirements as $requirement) {
            yield $requirement;
        }
    }
}
