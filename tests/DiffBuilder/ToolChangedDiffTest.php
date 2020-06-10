<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\DiffBuilder;

use Phpcq\RepositoryBuilder\DiffBuilder\ToolChangedDiff;
use Phpcq\RepositoryBuilder\Repository\InlineBootstrap;
use Phpcq\RepositoryBuilder\Repository\Tool;
use Phpcq\RepositoryBuilder\Repository\ToolHash;
use Phpcq\RepositoryBuilder\Repository\ToolVersion;
use Phpcq\RepositoryBuilder\Repository\VersionRequirement;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\RepositoryBuilder\DiffBuilder\ToolChangedDiff
 */
final class ToolChangedDiffTest extends TestCase
{
    public function testAddsNewVersionsCorrectly(): void
    {
        $old = $this->mockToolWithVersions('tool-name', []);
        $new = $this->mockToolWithVersions('tool-name', ['1.0.0', '2.0.0']);

        $this->assertInstanceOf(ToolChangedDiff::class, $diff = ToolChangedDiff::diff($old, $new));
        $this->assertSame(
            <<<EOF
            Changes for tool-name:
              Added version 1.0.0
              Added version 2.0.0

            EOF,
            $diff->__toString()
        );
        $this->assertSame('tool-name', $diff->getToolName());
    }

    public function testAddsRemovedVersionsCorrectly(): void
    {
        $old = $this->mockToolWithVersions('tool-name', ['1.0.0', '2.0.0']);
        $new = $this->mockToolWithVersions('tool-name', []);

        $this->assertInstanceOf(ToolChangedDiff::class, $diff = ToolChangedDiff::diff($old, $new));
        $this->assertSame(
            <<<EOF
            Changes for tool-name:
              Removed version 1.0.0
              Removed version 2.0.0

            EOF,
            $diff->__toString()
        );
        $this->assertSame('tool-name', $diff->getToolName());
    }

    public function testReturnsNullOnNochanges(): void
    {
        $old = $this->mockToolWithVersions('tool-name', ['1.0.0', '2.0.0']);
        $new = $this->mockToolWithVersions('tool-name', ['1.0.0', '2.0.0']);

        $this->assertNull(ToolChangedDiff::diff($old, $new));
    }

    public function testAddsChangedVersionCorrectly(): void
    {
        $old = new Tool('tool-name');
        $new = new Tool('tool-name');

        $oldVersion = new ToolVersion(
            'tool-name',
            '1.0.0',
            'https://example.org/old.phar',
            [
                new VersionRequirement('php', '^7.3'),
            ],
            new ToolHash('sha-1', 'old-hash'),
            'https://example.org/old.phar.asc',
            new InlineBootstrap('1.0.0', '<?php // old bootstrap...', null)
        );
        $newVersion = new ToolVersion(
            'tool-name',
            '1.0.0',
            'https://example.org/new.phar',
            [
                new VersionRequirement('php', '^7.4'),
            ],
            new ToolHash('sha-512', 'new-hash'),
            'https://example.org/new.phar.asc',
            new InlineBootstrap('1.0.0', '<?php // new bootstrap...', null)
        );

        $old->addVersion($oldVersion);
        $new->addVersion($newVersion);

        $md5Old = md5('<?php // old bootstrap...');
        $md5New = md5('<?php // new bootstrap...');

        $this->assertInstanceOf(ToolChangedDiff::class, $diff = ToolChangedDiff::diff($old, $new));
        $this->assertSame(
            <<<EOF
            Changes for tool-name:
              Changed version 1.0.0:
                phar-url:
                  - https://example.org/old.phar
                  + https://example.org/new.phar
                requirements:
                  - php:^7.3
                  + php:^7.4
                hash:
                  - sha-1:old-hash
                  + sha-512:new-hash
                signature:
                  - https://example.org/old.phar.asc
                  + https://example.org/new.phar.asc
                bootstrap:
                  - inline:1.0.0:$md5Old
                  + inline:1.0.0:$md5New

            EOF,
            $diff->__toString()
        );
        $this->assertSame('tool-name', $diff->getToolName());
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
                new InlineBootstrap('1.0.0', '<?php // bootstrap...', null)
            ));
        }

        return $tool;
    }
}
