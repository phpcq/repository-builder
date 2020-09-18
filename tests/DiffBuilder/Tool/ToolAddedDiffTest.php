<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\DiffBuilder\Tool;

use Phpcq\RepositoryBuilder\DiffBuilder\Tool\ToolAddedDiff;
use Phpcq\RepositoryDefinition\Tool\Tool;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\RepositoryBuilder\DiffBuilder\ObjectAddedDiffTrait
 * @covers \Phpcq\RepositoryBuilder\DiffBuilder\ObjectDiffTrait
 * @covers \Phpcq\RepositoryBuilder\DiffBuilder\Tool\ToolAddedDiff
 */
final class ToolAddedDiffTest extends TestCase
{
    use ToolDiffTrait;

    public function testIgnoresToolWithoutVersions(): void
    {
        $new = new Tool('tool-name');

        $this->assertInstanceOf(ToolAddedDiff::class, $diff = ToolAddedDiff::diff($new));
        $this->assertSame('', $diff->__toString());
    }

    public function testAddsAllVersionsAsNew(): void
    {
        $new = $this->mockToolWithVersions('tool-name', ['1.0.0', '2.0.0']);

        $this->assertInstanceOf(ToolAddedDiff::class, $diff = ToolAddedDiff::diff($new));
        $this->assertSame(
            <<<EOF
            Added tool-name:
              Added version 1.0.0
              Added version 2.0.0

            EOF,
            $diff->__toString()
        );
    }
}
