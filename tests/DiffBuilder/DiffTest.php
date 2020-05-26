<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\DiffBuilder;

use Phpcq\RepositoryBuilder\DiffBuilder\Diff;
use Phpcq\RepositoryBuilder\Repository\InlineBootstrap;
use Phpcq\RepositoryBuilder\Repository\Tool;
use Phpcq\RepositoryBuilder\Repository\ToolHash;
use Phpcq\RepositoryBuilder\Repository\ToolVersion;
use Phpcq\RepositoryBuilder\Repository\VersionRequirement;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\RepositoryBuilder\DiffBuilder\Diff
 */
final class DiffTest extends TestCase
{
    public function testProcessesCorrectly(): void
    {
        $old = new Tool('test-tool');
        // Versions to remove.
        $old->addVersion(new ToolVersion('test-tool', '0.1.0', null, null, null, null, null));
        $old->addVersion(new ToolVersion('test-tool', '0.2.0', null, null, null, null, null));
        // Version to change.
        $old->addVersion(
            new ToolVersion(
                'test-tool',
                '0.5.0',
                'https://example.org/old.phar',
                [
                    new VersionRequirement('php', '^7.3'),
                ],
                new ToolHash('sha-1', 'old-hash'),
                'https://example.org/old.phar.asc',
                new InlineBootstrap('1.0.0', '<?php // old bootstrap...')
            )
        );

        $new = new Tool('test-tool');
        // Versions to add.
        $new->addVersion(new ToolVersion('test-tool', '1.0.0', null, null, null, null, null));
        $new->addVersion(new ToolVersion('test-tool', '2.0.0', null, null, null, null, null));
        // Version to change.
        $new->addVersion(
            new ToolVersion(
                'test-tool',
                '0.5.0',
                'https://example.org/new.phar',
                [
                    new VersionRequirement('php', '^7.4'),
                ],
                new ToolHash('sha-512', 'new-hash'),
                'https://example.org/new.phar.asc',
                new InlineBootstrap('1.0.0', '<?php // new bootstrap...')
            )
        );

        $deleteTool = new Tool('delete-tool');
        $deleteTool->addVersion(new ToolVersion('delete-tool', '1.0.0', null, null, null, null, null));

        $addTool = new Tool('add-tool');
        $addTool->addVersion(new ToolVersion('add-tool', '1.0.0', null, null, null, null, null));

        $diff = Diff::diff(
            ['test-tool' => $old, 'delete-tool' => $deleteTool],
            ['test-tool' => $new, 'add-tool' => $addTool]
        );

        $this->assertSame(<<<EOF
            Changes in repository:
              Added add-tool:
                Added version 1.0.0
              Removed delete-tool:
                Removed version 1.0.0
              Changes for test-tool:
                Removed version 0.1.0
                Removed version 0.2.0
                Changed version 0.5.0:
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
                    - inline:1.0.0:c5879adbbae0670b7fba764092e66beb
                    + inline:1.0.0:64d660def5a58dc88e958bf71a95528a
                Added version 1.0.0
                Added version 2.0.0

            EOF, (string) $diff);
    }

    public function testProcessesEmptyDeletion(): void
    {
        $this->assertNull(Diff::diff([], null));
    }

    public function testProcessesEmptyAddition(): void
    {
        $this->assertNull(Diff::diff(null, []));
    }
}
