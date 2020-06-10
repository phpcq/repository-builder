<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\DiffBuilder;

use Phpcq\RepositoryBuilder\DiffBuilder\VersionRemovedDiff;
use Phpcq\RepositoryBuilder\Repository\InlineBootstrap;
use Phpcq\RepositoryBuilder\Repository\ToolHash;
use Phpcq\RepositoryBuilder\Repository\ToolVersion;
use Phpcq\RepositoryBuilder\Repository\VersionRequirement;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\RepositoryBuilder\DiffBuilder\VersionRemovedDiff
 */
final class VersionRemovedDiffTest extends TestCase
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
            new InlineBootstrap('1.0.0', '<?php // old bootstrap...', null)
        );

        $this->assertInstanceOf(VersionRemovedDiff::class, $diff = VersionRemovedDiff::diff($oldVersion));

        $this->assertSame(
            <<<EOF
            Removed version 1.0.0

            EOF,
            $diff->__toString()
        );
        $this->assertSame('tool-name', $diff->getToolName());
        $this->assertSame('1.0.0', $diff->getVersion());
        $this->assertSame([
            'phar-url' => [
                'https://example.org/old.phar',
                null
            ],
            'requirements' => [
                'php:^7.3',
                null
            ],
            'hash' => [
                'sha-1:old-hash',
                null
            ],
            'signature' => [
                'https://example.org/old.phar.asc',
                null
            ],
            'bootstrap' => [
                'inline:1.0.0:c5879adbbae0670b7fba764092e66beb',
                null
            ]
        ], $diff->getDifferences());
    }
}
