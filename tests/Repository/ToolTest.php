<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\Repository;

use InvalidArgumentException;
use LogicException;
use Phpcq\RepositoryBuilder\Repository\Tool;
use Phpcq\RepositoryBuilder\Repository\ToolVersion;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\RepositoryBuilder\Repository\Tool
 */
class ToolTest extends TestCase
{
    public function testToolInitializesName(): void
    {
        $tool = new Tool('supertool');
        $this->assertSame('supertool', $tool->getName());
        $this->assertSame([], iterator_to_array($tool->getIterator()));
    }

    public function testToolAddsVersion(): void
    {
        $tool = new Tool('supertool');

        $tool->addVersion($version1 = new ToolVersion('supertool', '1.0.0', null, null, null, null, null));
        $tool->addVersion($version2 = new ToolVersion('supertool', '2.0.0', null, null, null, null, null));
        $this->assertSame([$version1, $version2], iterator_to_array($tool->getIterator()));
    }

    public function testToolThrowsWhenAddingAVersionForAnotherTool(): void
    {
        $tool = new Tool('supertool');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tool name mismatch: anothertool');

        $tool->addVersion(new ToolVersion('anothertool', '1.0.0', null, null, null, null, null));
    }

    public function testToolThrowsWhenAddingAVersionTwice(): void
    {
        $tool = new Tool('supertool');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Version already added: 1.0.0');

        $tool->addVersion(new ToolVersion('supertool', '1.0.0', null, null, null, null, null));
        $tool->addVersion(new ToolVersion('supertool', '1.0.0', null, null, null, null, null));
    }

    public function testToolGetsVersion(): void
    {
        $tool = new Tool('supertool');

        $tool->addVersion($version1 = new ToolVersion('supertool', '1.0.0', null, null, null, null, null));
        $tool->addVersion($version2 = new ToolVersion('supertool', '2.0.0', null, null, null, null, null));

        $this->assertSame($version1, $tool->getVersion('1.0.0'));
        $this->assertSame($version2, $tool->getVersion('2.0.0'));
    }

    public function testToolThrowsWhenFetchingAnUnknownVersion(): void
    {
        $tool = new Tool('supertool');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Version not added: 1.0.0');

        $tool->getVersion('1.0.0');
    }
}
