<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\DiffBuilder;

use Closure;
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
            Update versions of "add-tool", "delete-tool", "test-tool"

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

    public function testProcessesEmptyRemoval(): void
    {
        $this->assertNull(Diff::removed([]));
    }

    public function testProcessesEmptyCreation(): void
    {
        $this->assertNull(Diff::created([]));
    }

    public function summaryProvider(): array
    {
        return [
            'New tool added' => [
                'expected' => 'Add tool "tool-name"',
                'changes' => Closure::fromCallable(function () {
                    $tool = new Tool('tool-name');

                    return Diff::created(['tool-name' => $tool]);
                })->__invoke(),
            ],
            'Old tool removed' => [
                'expected' => 'Remove tool "tool-name"',
                'changes' => Closure::fromCallable(function () {
                    $tool = new Tool('tool-name');

                    return Diff::removed(['tool-name' => $tool]);
                })->__invoke(),
            ],
            'Only one version has been added' => [
                'expected' => 'Add version 1.0.0 of tool "tool-name"',
                'changes' => Closure::fromCallable(function () {
                    $toolOld = new Tool('tool-name');
                    $toolNew = new Tool('tool-name');

                    $toolNew->addVersion(new ToolVersion('tool-name', '1.0.0', null, null, null, null, null));

                    return Diff::diff(['tool-name' => $toolOld], ['tool-name' => $toolNew]);
                })->__invoke(),
            ],
            'Only one version has been removed' => [
                'expected' => 'Remove version 1.0.0 of tool "tool-name"',
                'changes' => Closure::fromCallable(function () {
                    $toolOld = new Tool('tool-name');
                    $toolNew = new Tool('tool-name');

                    $toolOld->addVersion(new ToolVersion('tool-name', '1.0.0', null, null, null, null, null));

                    return Diff::diff(['tool-name' => $toolOld], ['tool-name' => $toolNew]);
                })->__invoke(),
            ],
            'Only one version has been changed' => [
                'expected' => 'Update version 1.0.0 of tool "tool-name"',
                'changes' => Closure::fromCallable(function () {
                    $toolOld = new Tool('tool-name');
                    $toolNew = new Tool('tool-name');

                    $toolOld->addVersion(new ToolVersion('tool-name', '1.0.0', null, null, null, null, null));
                    $toolNew->addVersion(new ToolVersion('tool-name', '1.0.0', 'changed', null, null, null, null));

                    return Diff::diff(['tool-name' => $toolOld], ['tool-name' => $toolNew]);
                })->__invoke(),
            ],
            'Multiple changes have happened for one tool' => [
                'expected' => 'Update tool "tool-name": 3 new versions, 1 versions deleted, 2 versions changed',
                'changes' => Closure::fromCallable(function () {
                    $toolOld = new Tool('tool-name');
                    $toolNew = new Tool('tool-name');

                    // 3 new versions:
                    $toolNew->addVersion(new ToolVersion('tool-name', '1.0.0', 'changed', null, null, null, null));
                    $toolNew->addVersion(new ToolVersion('tool-name', '1.0.1', 'changed', null, null, null, null));
                    $toolNew->addVersion(new ToolVersion('tool-name', '1.0.2', 'changed', null, null, null, null));

                    // 1 version deleted:
                    $toolOld->addVersion(new ToolVersion('tool-name', '1.0.3', null, null, null, null, null));

                    // 2 versions changed:
                    $toolOld->addVersion(new ToolVersion('tool-name', '2.0.0', null, null, null, null, null));
                    $toolNew->addVersion(new ToolVersion('tool-name', '2.0.0', 'changed', null, null, null, null));
                    $toolOld->addVersion(new ToolVersion('tool-name', '2.0.1', null, null, null, null, null));
                    $toolNew->addVersion(new ToolVersion('tool-name', '2.0.1', 'changed', null, null, null, null));

                    return Diff::diff(['tool-name' => $toolOld], ['tool-name' => $toolNew]);
                })->__invoke(),
            ],

            'Two tools changed' => [
                'expected' => 'Update versions of "tool-name-1", "tool-name-2"',
                'changes' => Closure::fromCallable(function () {
                    $tool1Old = new Tool('tool-name-1');
                    $tool1New = new Tool('tool-name-1');
                    $tool2Old = new Tool('tool-name-2');
                    $tool2New = new Tool('tool-name-2');

                    $tool1New->addVersion(new ToolVersion('tool-name-1', '1.0.0', null, null, null, null, null));
                    $tool2New->addVersion(new ToolVersion('tool-name-2', '1.0.0', null, null, null, null, null));

                    return Diff::diff(
                        ['tool-name-1' => $tool1Old, 'tool-name-2' => $tool2Old],
                        ['tool-name-1' => $tool1New, 'tool-name-2' => $tool2New]
                    );
                })->__invoke(),
            ],

            'Up to 3 tools changed' => [
                'expected' => 'Update versions of "tool-name-1", "tool-name-2", "tool-name-3"',
                'changes' => Closure::fromCallable(function () {
                    $tool1Old = new Tool('tool-name-1');
                    $tool1New = new Tool('tool-name-1');
                    $tool2Old = new Tool('tool-name-2');
                    $tool2New = new Tool('tool-name-2');
                    $tool3Old = new Tool('tool-name-3');
                    $tool3New = new Tool('tool-name-3');

                    $tool1New->addVersion(new ToolVersion('tool-name-1', '1.0.0', null, null, null, null, null));
                    $tool2New->addVersion(new ToolVersion('tool-name-2', '1.0.0', null, null, null, null, null));
                    $tool3New->addVersion(new ToolVersion('tool-name-3', '1.0.0', null, null, null, null, null));

                    return Diff::diff(
                        ['tool-name-1' => $tool1Old, 'tool-name-2' => $tool2Old, 'tool-name-3' => $tool3Old],
                        ['tool-name-1' => $tool1New, 'tool-name-2' => $tool2New, 'tool-name-3' => $tool3New]
                    );
                })->__invoke(),
            ],

            'More than 3 tools changed' => [
                'expected' => 'Update versions of "tool-name-1", "tool-name-2" and 2 more tools',
                'changes' => Closure::fromCallable(function () {
                    $tool1Old = new Tool('tool-name-1');
                    $tool1New = new Tool('tool-name-1');
                    $tool2Old = new Tool('tool-name-2');
                    $tool2New = new Tool('tool-name-2');
                    $tool3Old = new Tool('tool-name-3');
                    $tool3New = new Tool('tool-name-3');
                    $tool4Old = new Tool('tool-name-4');
                    $tool4New = new Tool('tool-name-4');

                    $tool1New->addVersion(new ToolVersion('tool-name-1', '1.0.0', null, null, null, null, null));
                    $tool2New->addVersion(new ToolVersion('tool-name-2', '1.0.0', null, null, null, null, null));
                    $tool3New->addVersion(new ToolVersion('tool-name-3', '1.0.0', null, null, null, null, null));
                    $tool4New->addVersion(new ToolVersion('tool-name-4', '1.0.0', null, null, null, null, null));

                    return Diff::diff(
                        [
                            'tool-name-1' => $tool1Old,
                            'tool-name-2' => $tool2Old,
                            'tool-name-3' => $tool3Old,
                            'tool-name-4' => $tool4Old
                        ],
                        [
                            'tool-name-1' => $tool1New,
                            'tool-name-2' => $tool2New,
                            'tool-name-3' => $tool3New,
                            'tool-name-4' => $tool4New
                        ]
                    );
                })->__invoke(),
            ],
        ];
    }

    /**
     * @dataProvider summaryProvider
     */
    public function testSummary(string $expected, Diff $changes): void
    {
        $this->assertSame($expected, $changes->asSummary());
    }
}
