<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\DiffBuilder;

use Phpcq\RepositoryBuilder\DiffBuilder\VersionAddedDiff;
use Phpcq\RepositoryBuilder\Repository\InlineBootstrap;
use Phpcq\RepositoryBuilder\Repository\ToolHash;
use Phpcq\RepositoryBuilder\Repository\ToolVersion;
use Phpcq\RepositoryBuilder\Repository\VersionRequirement;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\RepositoryBuilder\DiffBuilder\VersionAddedDiff
 */
final class VersionAddedDiffTest extends TestCase
{
    public function testCreation(): void
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

        $this->assertInstanceOf(VersionAddedDiff::class, $diff = VersionAddedDiff::diff($oldVersion));

        $this->assertSame(
            <<<EOF
            Added version 1.0.0

            EOF,
            $diff->__toString()
        );
        $this->assertSame('tool-name', $diff->getToolName());
        $this->assertSame('1.0.0', $diff->getVersion());
        $this->assertSame([
            'phar-url' => [
                null,
                'https://example.org/old.phar',
            ],
            'requirements' => [
                null,
                'php:^7.3',
            ],
            'hash' => [
                null,
                'sha-1:old-hash',
            ],
            'signature' => [
                null,
                'https://example.org/old.phar.asc',
            ],
            'bootstrap' => [
                null,
                'inline:1.0.0:c5879adbbae0670b7fba764092e66beb',
            ]
        ], $diff->getDifferences());
    }
}
