<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\DiffBuilder;

use Phpcq\RepositoryBuilder\DiffBuilder\VersionChangedDiff;
use Phpcq\RepositoryBuilder\Repository\InlineBootstrap;
use Phpcq\RepositoryBuilder\Repository\ToolHash;
use Phpcq\RepositoryBuilder\Repository\ToolVersion;
use Phpcq\RepositoryBuilder\Repository\VersionRequirement;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\RepositoryBuilder\DiffBuilder\VersionChangedDiff
 */
final class VersionChangedDiffTest extends TestCase
{
    public function testIgnoresEmptyDiff(): void
    {
        $oldVersion = new ToolVersion(
            'tool-name',
            '1.0.0',
            'https://example.org/old.phar',
            [
                new VersionRequirement('php', '^7.3'),
            ],
            new ToolHash('sha-1', 'old-hash'),
            'https://example.org/old.phar.asc',
            new InlineBootstrap('1.0.0', '<?php // old bootstrap...')
        );

        $this->assertNull(VersionChangedDiff::diff($oldVersion, $oldVersion));
    }

    public function compareTestProvider(): array
    {
        $md5Old = md5('<?php // old bootstrap...');
        $md5New = md5('<?php // new bootstrap...');

        return [
            'Change phar-url' => [
                'expected' => <<<EOF
                Changed version 1.0.0:
                  phar-url:
                    - https://example.org/old.phar
                    + https://example.org/new.phar

                EOF,
                'old' => new ToolVersion(
                    'tool-name',
                    '1.0.0',
                    'https://example.org/old.phar',
                    null,
                    null,
                    null,
                    null,
                ),
                'new' => new ToolVersion(
                    'tool-name',
                    '1.0.0',
                    'https://example.org/new.phar',
                    null,
                    null,
                    null,
                    null,
                ),
            ],
            'Change requirements' => [
                'expected' => <<<EOF
                Changed version 1.0.0:
                  requirements:
                    - php:^7.3
                    + php:^7.4

                EOF,
                'old' => new ToolVersion(
                    'tool-name',
                    '1.0.0',
                    null,
                    [
                        new VersionRequirement('php', '^7.3'),
                    ],
                    null,
                    null,
                    null
                ),
                'new' => new ToolVersion(
                    'tool-name',
                    '1.0.0',
                    null,
                    [
                        new VersionRequirement('php', '^7.4'),
                    ],
                    null,
                    null,
                    null
                ),
            ],
            'Change hash' => [
                'expected' => <<<EOF
                Changed version 1.0.0:
                  hash:
                    - sha-1:old-hash
                    + sha-512:new-hash

                EOF,
                'old' => new ToolVersion(
                    'tool-name',
                    '1.0.0',
                    null,
                    null,
                    new ToolHash('sha-1', 'old-hash'),
                    null,
                    null
                ),
                'new' => new ToolVersion(
                    'tool-name',
                    '1.0.0',
                    null,
                    null,
                    new ToolHash('sha-512', 'new-hash'),
                    null,
                    null
                ),
            ],
            'Change signature' => [
                'expected' => <<<EOF
                Changed version 1.0.0:
                  signature:
                    - https://example.org/old.phar.asc
                    + https://example.org/new.phar.asc

                EOF,
                'old' => new ToolVersion(
                    'tool-name',
                    '1.0.0',
                    null,
                    null,
                    null,
                    'https://example.org/old.phar.asc',
                    null
                ),
                'new' => new ToolVersion(
                    'tool-name',
                    '1.0.0',
                    null,
                    null,
                    null,
                    'https://example.org/new.phar.asc',
                    null
                ),
            ],
            'Change bootstrap' => [
                'expected' => <<<EOF
                Changed version 1.0.0:
                  bootstrap:
                    - inline:1.0.0:$md5Old
                    + inline:1.0.0:$md5New

                EOF,
                'old' => new ToolVersion(
                    'tool-name',
                    '1.0.0',
                    null,
                    null,
                    null,
                    null,
                    new InlineBootstrap('1.0.0', '<?php // old bootstrap...')
                ),
                'new' => new ToolVersion(
                    'tool-name',
                    '1.0.0',
                    null,
                    null,
                    null,
                    null,
                    new InlineBootstrap('1.0.0', '<?php // new bootstrap...')
                ),
            ],
        ];
    }

    /**
     * @dataProvider compareTestProvider
     */
    public function testProcessesCorrectly(string $expected, ToolVersion $oldVersion, ToolVersion $newVersion): void
    {
        $this->assertInstanceOf(VersionChangedDiff::class, $diff = VersionChangedDiff::diff($oldVersion, $newVersion));
        $this->assertSame('tool-name', $diff->getToolName());
        $this->assertSame('1.0.0', $diff->getVersion());

        $this->assertSame($expected, $diff->__toString());
    }
}
