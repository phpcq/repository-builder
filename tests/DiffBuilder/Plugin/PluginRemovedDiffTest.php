<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\DiffBuilder\Plugin;

use Phpcq\RepositoryBuilder\DiffBuilder\Plugin\PluginRemovedDiff;
use Phpcq\RepositoryDefinition\Plugin\Plugin;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\RepositoryBuilder\DiffBuilder\ObjectDiffTrait
 * @covers \Phpcq\RepositoryBuilder\DiffBuilder\ObjectRemovedDiffTrait
 * @covers \Phpcq\RepositoryBuilder\DiffBuilder\Plugin\PluginRemovedDiff
 */
final class PluginRemovedDiffTest extends TestCase
{
    use PluginDiffTrait;

    public function testIgnoresToolWithoutVersions(): void
    {
        $new = new Plugin('tool-name');

        $this->assertInstanceOf(PluginRemovedDiff::class, $diff = PluginRemovedDiff::diff($new));
        $this->assertSame('', $diff->__toString());
    }

    public function testAddsAllVersionsAsNew(): void
    {
        $old = $this->mockPluginWithVersions('tool-name', ['1.0.0', '2.0.0']);

        $this->assertInstanceOf(PluginRemovedDiff::class, $diff = PluginRemovedDiff::diff($old));
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
