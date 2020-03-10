<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\Repository;

use Phpcq\RepositoryBuilder\Repository\VersionRequirement;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\RepositoryBuilder\Repository\VersionRequirement
 */
class VersionRequirementTest extends TestCase
{
    public function testVersionRequirementInitializes(): void
    {
        $tool = new VersionRequirement('ext-foo', '*');
        $this->assertSame('ext-foo', $tool->getName());
        $this->assertSame('*', $tool->getConstraint());
    }
}