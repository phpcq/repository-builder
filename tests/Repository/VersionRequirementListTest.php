<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\Repository;

use LogicException;
use Phpcq\RepositoryBuilder\Repository\VersionRequirement;
use Phpcq\RepositoryBuilder\Repository\VersionRequirementList;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\RepositoryBuilder\Repository\VersionRequirementList
 */
class VersionRequirementListTest extends TestCase
{
    public function testVersionRequirementListCanBeCreated(): void
    {
        $requirements = new VersionRequirementList();

        $this->assertSame([], iterator_to_array($requirements->getIterator()));
        $this->assertFalse($requirements->has('test2'));
    }

    public function testVersionRequirementListInitializesWithPassedRequirements(): void
    {
        $requirement1 = new VersionRequirement('test1');
        $requirement2 = new VersionRequirement('test2');
        $requirements = new VersionRequirementList([$requirement1, $requirement2]);

        $this->assertSame([$requirement1, $requirement2], iterator_to_array($requirements->getIterator()));
        $this->assertTrue($requirements->has('test1'));
        $this->assertTrue($requirements->has('test2'));
    }

    public function testVersionRequirementListThrowsForDuplicateRequirement(): void
    {
        $requirements = new VersionRequirementList([new VersionRequirement('test')]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Requirement already added for test');

        $requirements->add(new VersionRequirement('test'));
    }

    public function testVersionRequirementListCanRetrieveRequirement(): void
    {
        $requirements = new VersionRequirementList([$requirement = new VersionRequirement('test')]);

        $this->assertSame($requirement, $requirements->get('test'));
    }

    public function testVersionRequirementListThrowsWhenRetrievingUnknownRequirement(): void
    {
        $requirements = new VersionRequirementList();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Requirement not added for test');

        $requirements->get('test');
    }
}