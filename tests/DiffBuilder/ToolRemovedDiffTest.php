<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\DiffBuilder;

use Phpcq\RepositoryBuilder\DiffBuilder\ToolRemovedDiff;
use Phpcq\RepositoryBuilder\Repository\InlineBootstrap;
use Phpcq\RepositoryBuilder\Repository\Tool;
use Phpcq\RepositoryBuilder\Repository\ToolHash;
use Phpcq\RepositoryBuilder\Repository\ToolVersion;
use Phpcq\RepositoryBuilder\Repository\VersionRequirement;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\RepositoryBuilder\DiffBuilder\ToolRemovedDiff
 */
final class ToolRemovedDiffTest extends TestCase
{
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

    private function mockToolWithVersions(string $toolName, array $versions): Tool
    {
        $tool = new Tool($toolName);
        foreach ($versions as $version) {
            $tool->addVersion(new ToolVersion(
                $toolName,
                $version,
                'https://example.org/' . $toolName . '-' . $version . '.phar',
                [
                    new VersionRequirement('php', '^7.4'),
                ],
                new ToolHash('sha-512', $toolName . '-' . $version . '-hash'),
                'https://example.org/' . $toolName . '-' . $version . '.phar.asc',
                new InlineBootstrap('1.0.0', '<?php // bootstrap...')
            ));
        }

        return $tool;
    }
}
