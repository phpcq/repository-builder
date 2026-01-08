<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\DiffBuilder\Tool;

use Phpcq\RepositoryBuilder\DiffBuilder\ObjectDiffTrait;
use Phpcq\RepositoryBuilder\DiffBuilder\ObjectRemovedDiffTrait;
use Phpcq\RepositoryBuilder\DiffBuilder\Tool\ToolRemovedDiff;
use Phpcq\RepositoryDefinition\Tool\Tool;
use PHPUnit\Framework\TestCase;

#[CoversClass(ObjectDiffTrait::class)]
#[CoversClass(ObjectRemovedDiffTrait::class)]
#[CoversClass(ToolRemovedDiff::class)]
final class ToolRemovedDiffTest extends TestCase
{
    use ToolDiffTrait;

    public function testIgnoresToolWithoutVersions(): void
    {
        $new = new Tool('tool-name');

        $this->assertInstanceOf(ToolRemovedDiff::class, $diff = ToolRemovedDiff::diff($new));
        $this->assertSame('', $diff->__toString());
    }

    public function testAddsAllVersionsAsNew(): void
    {
        $new = $this->mockToolWithVersions('tool-name', ['1.0.0', '2.0.0']);

        $this->assertInstanceOf(ToolRemovedDiff::class, $diff = ToolRemovedDiff::diff($new));
        $this->assertSame(
            <<<EOF
            Removed tool-name:
              Removed version 1.0.0
              Removed version 2.0.0

            EOF,
            $diff->__toString()
        );
    }
}
