<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\DiffBuilder\Plugin;

use Phpcq\RepositoryBuilder\DiffBuilder\ObjectAddedDiffTrait;
use Phpcq\RepositoryBuilder\DiffBuilder\ObjectDiffTrait;
use Phpcq\RepositoryBuilder\DiffBuilder\Plugin\PluginAddedDiff;
use Phpcq\RepositoryDefinition\Plugin\Plugin;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ObjectAddedDiffTrait::class)]
#[CoversClass(ObjectDiffTrait::class)]
#[CoversClass(PluginAddedDiff::class)]
final class PluginAddedDiffTest extends TestCase
{
    use PluginDiffTrait;

    public function testIgnoresToolWithoutVersions(): void
    {
        $new = new Plugin('tool-name');

        $this->assertInstanceOf(PluginAddedDiff::class, $diff = PluginAddedDiff::diff($new));
        $this->assertSame('', $diff->__toString());
    }

    public function testAddsAllVersionsAsNew(): void
    {
        $new = $this->mockPluginWithVersions('tool-name', ['1.0.0', '2.0.0']);

        $this->assertInstanceOf(PluginAddedDiff::class, $diff = PluginAddedDiff::diff($new));
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
