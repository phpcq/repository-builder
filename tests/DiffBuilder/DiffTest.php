<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\DiffBuilder;

use Closure;
use Generator;
use Phpcq\RepositoryBuilder\DiffBuilder\Diff;
use Phpcq\RepositoryBuilder\Test\DiffBuilder\Plugin\PluginDiffTrait;
use Phpcq\RepositoryDefinition\Plugin\Plugin;
use Phpcq\RepositoryDefinition\Plugin\PluginHash;
use Phpcq\RepositoryDefinition\Plugin\PluginRequirements;
use Phpcq\RepositoryDefinition\Tool\Tool;
use Phpcq\RepositoryDefinition\Tool\ToolHash;
use Phpcq\RepositoryDefinition\Tool\ToolRequirements;
use Phpcq\RepositoryDefinition\Tool\ToolVersion;
use Phpcq\RepositoryDefinition\VersionRequirement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Diff::class)]
final class DiffTest extends TestCase
{
    use PluginDiffTrait;

    /** @SuppressWarnings(PHPMD.ExcessiveMethodLength) */
    public function testProcessesCorrectly(): void
    {
        $oldPlugin = new Plugin('test-plugin');
        // Versions to remove.
        $oldPlugin->addVersion($this->mockPluginVersion('test-plugin', '0.1.0'));
        $oldPlugin->addVersion($this->mockPluginVersion('test-plugin', '0.2.0'));
        // Version to change.
        $oldRequirements = new PluginRequirements();
        $oldRequirements->getPhpRequirements()->add(new VersionRequirement('php', '^7.3'));
        $oldPlugin->addVersion(
            $this->mockPhpFilePluginVersionInterface(
                'test-plugin',
                '0.5.0',
                'https://example.org/test-plugin-0.5.0.phar',
                $oldRequirements,
                PluginHash::create('sha-1', 'old-hash'),
                'https://example.org/test-plugin-0.5.0.phar.asc',
            )
        );

        $newPlugin = new Plugin('test-plugin');
        // Versions to add.
        $newPlugin->addVersion($this->mockPluginVersion('test-plugin', '1.0.0'));
        $newPlugin->addVersion($this->mockPluginVersion('test-plugin', '2.0.0'));
        // Version to change.
        $newRequirements = new PluginRequirements();
        $newRequirements->getPhpRequirements()->add(new VersionRequirement('php', '^7.4'));
        $newPlugin->addVersion(
            $this->mockPhpFilePluginVersionInterface(
                'test-plugin',
                '0.5.0',
                'https://example.org/0.5.0/test-plugin.phar',
                $newRequirements,
                PluginHash::create('sha-512', 'new-hash'),
                'https://example.org/0.5.0/test-plugin.phar.asc',
            )
        );

        $deletePlugin = new Plugin('delete-plugin');
        $deletePlugin->addVersion($this->mockPluginVersion('delete-plugin', '1.0.0'));

        $addPlugin = new Plugin('add-plugin');
        $addPlugin->addVersion($this->mockPluginVersion('add-plugin', '1.0.0'));

        $oldTool = new Tool('test-tool');
        // Versions to remove.
        $oldTool->addVersion(new ToolVersion('test-tool', '0.1.0', null, null, null, null));
        $oldTool->addVersion(new ToolVersion('test-tool', '0.2.0', null, null, null, null));
        // Version to change.
        $oldRequirements = new ToolRequirements();
        $oldRequirements->getPhpRequirements()->add(new VersionRequirement('php', '^7.3'));
        $oldTool->addVersion(
            new ToolVersion(
                'test-tool',
                '0.5.0',
                'https://example.org/old.phar',
                $oldRequirements,
                ToolHash::create('sha-1', 'old-hash'),
                'https://example.org/old.phar.asc',
            )
        );

        $newTool = new Tool('test-tool');
        // Versions to add.
        $newTool->addVersion(new ToolVersion('test-tool', '1.0.0', null, null, null, null));
        $newTool->addVersion(new ToolVersion('test-tool', '2.0.0', null, null, null, null));
        // Version to change.
        $newRequirements = new ToolRequirements();
        $newRequirements->getPhpRequirements()->add(new VersionRequirement('php', '^7.4'));
        $newTool->addVersion(
            new ToolVersion(
                'test-tool',
                '0.5.0',
                'https://example.org/new.phar',
                $newRequirements,
                ToolHash::create('sha-512', 'new-hash'),
                'https://example.org/new.phar.asc',
            )
        );

        $deleteTool = new Tool('delete-tool');
        $deleteTool->addVersion(new ToolVersion('delete-tool', '1.0.0', null, null, null, null));

        $addTool = new Tool('add-tool');
        $addTool->addVersion(new ToolVersion('add-tool', '1.0.0', null, null, null, null));

        $diff = Diff::diff(
            ['test-plugin' => $oldPlugin, 'delete-plugin' => $deletePlugin],
            ['test-plugin' => $newPlugin, 'add-plugin' => $addPlugin],
            ['test-tool' => $oldTool, 'delete-tool' => $deleteTool],
            ['test-tool' => $newTool, 'add-tool' => $addTool],
        );

        $this->assertSame(<<<EOF
            Update versions of "add-plugin", "add-tool" and 4 more

            Changes in repository:
              Changed plugins:
                Added add-plugin:
                  Added version 1.0.0
                Removed delete-plugin:
                  Removed version 1.0.0
                Changes for test-plugin:
                  Removed version 0.1.0
                  Removed version 0.2.0
                  Changed version 0.5.0:
                    code:
                      - url:https://example.org/test-plugin-0.5.0.phar
                      + url:https://example.org/0.5.0/test-plugin.phar
                    requirements:
                      - platform: php:^7.3
                      + platform: php:^7.4
                    checksum:
                      - sha-1:old-hash
                      + sha-512:new-hash
                    signature:
                      - url:https://example.org/test-plugin-0.5.0.phar.asc
                      + url:https://example.org/0.5.0/test-plugin.phar.asc
                  Added version 1.0.0
                  Added version 2.0.0
              Changed tools:
                Added add-tool:
                  Added version 1.0.0
                Removed delete-tool:
                  Removed version 1.0.0
                Changes for test-tool:
                  Removed version 0.1.0
                  Removed version 0.2.0
                  Changed version 0.5.0:
                    url:
                      - https://example.org/old.phar
                      + https://example.org/new.phar
                    requirements:
                      - platform: php:^7.3
                      + platform: php:^7.4
                    checksum:
                      - sha-1:old-hash
                      + sha-512:new-hash
                    signature:
                      - https://example.org/old.phar.asc
                      + https://example.org/new.phar.asc
                  Added version 1.0.0
                  Added version 2.0.0

            EOF, (string) $diff);
    }

    public function testProcessesEmptyRemoval(): void
    {
        $this->assertNull(Diff::removed([], []));
    }

    public function testProcessesRemoval(): void
    {
        $deletePlugin = new Plugin('delete-plugin');
        $deletePlugin->addVersion($this->mockPluginVersion('delete-plugin', '1.0.0'));
        $deleteTool = new Tool('delete-tool');
        $deleteTool->addVersion(new ToolVersion('delete-tool', '1.0.0', null, null, null, null));

        $diff = Diff::removed(
            ['delete-plugin' => $deletePlugin],
            ['delete-tool' => $deleteTool],
        );

        $this->assertSame(<<<EOF
            Update versions of "delete-plugin", "delete-tool"
            
            Changes in repository:
              Changed plugins:
                Removed delete-plugin:
                  Removed version 1.0.0
              Changed tools:
                Removed delete-tool:
                  Removed version 1.0.0

            EOF, (string) $diff);
    }

    public function testProcessesEmptyCreation(): void
    {
        $this->assertNull(Diff::created([], []));
    }

    public function testProcessesCreation(): void
    {
        $newPlugin = new Plugin('test-plugin');
        $newPlugin->addVersion($this->mockPluginVersion('test-plugin', '1.0.0'));
        $newPlugin->addVersion($this->mockPluginVersion('test-plugin', '2.0.0'));
        $newTool = new Tool('test-tool');
        $newTool->addVersion(new ToolVersion('test-tool', '1.0.0', null, null, null, null));
        $newTool->addVersion(new ToolVersion('test-tool', '2.0.0', null, null, null, null));

        $diff = Diff::created(
            ['test-plugin' => $newPlugin],
            ['test-tool' => $newTool],
        );

        $this->assertSame(<<<EOF
            Update versions of "test-plugin", "test-tool"
            
            Changes in repository:
              Changed plugins:
                Added test-plugin:
                  Added version 1.0.0
                  Added version 2.0.0
              Changed tools:
                Added test-tool:
                  Added version 1.0.0
                  Added version 2.0.0

            EOF, (string) $diff);
    }

    public function testProcessesEmptyDiff(): void
    {
        $testPluginOld = new Plugin('test-plugin');
        $testPluginOld->addVersion($this->mockPhpFilePluginVersionInterface('test-plugin', '1.0.0'));
        $testPluginOld->addVersion($this->mockPhpFilePluginVersionInterface('test-plugin', '2.0.0'));

        $testPluginNew = new Plugin('test-plugin');
        $testPluginNew->addVersion($this->mockPhpFilePluginVersionInterface('test-plugin', '1.0.0'));
        $testPluginNew->addVersion($this->mockPhpFilePluginVersionInterface('test-plugin', '2.0.0'));

        $testToolOld = new Tool('test-tool');
        $testToolOld->addVersion(new ToolVersion('test-tool', '1.0.0', null, null, null, null));
        $testToolOld->addVersion(new ToolVersion('test-tool', '2.0.0', null, null, null, null));

        $testToolNew = new Tool('test-tool');
        $testToolNew->addVersion(new ToolVersion('test-tool', '1.0.0', null, null, null, null));
        $testToolNew->addVersion(new ToolVersion('test-tool', '2.0.0', null, null, null, null));

        $this->assertNull(Diff::diff(
            ['test-plugin' => $testPluginOld],
            ['test-plugin' => $testPluginNew],
            ['test-tool' => $testToolOld],
            ['test-tool' => $testToolNew],
        ));
    }

    /** @SuppressWarnings(PHPMD.ExcessiveMethodLength) */
    public static function asStringProvider(): Generator
    {
        yield 'New tool added' => [
            'expected' => <<<EOF
                Add tool "tool-name"

                Changes in repository:
                  Changed tools:
                    Added tool-name:
                      Added version 1.0.0

                EOF,
            'changes' => (static function () {
                $tool = new Tool('tool-name');
                $tool->addVersion(new ToolVersion('tool-name', '1.0.0', null, null, null, null));

                return Diff::created([], ['tool-name' => $tool]);
            })(),
        ];
        yield 'Old tool removed' => [
            'expected' => <<<EOF
                Remove tool "tool-name"

                Changes in repository:
                  Changed tools:
                    Removed tool-name:
                      Removed version 1.0.0

                EOF,
            'changes' => (static function () {
                $tool = new Tool('tool-name');
                $tool->addVersion(new ToolVersion('tool-name', '1.0.0', null, null, null, null));

                return Diff::removed([], ['tool-name' => $tool]);
            })(),
        ];
        yield 'Only one version has been added' => [
            'expected' => <<<EOF
                Add version 1.0.0 of tool "tool-name"

                Changes in repository:
                  Changed tools:
                    Changes for tool-name:
                      Added version 1.0.0

                EOF,
            'changes' => (static function () {
                $toolOld = new Tool('tool-name');
                $toolNew = new Tool('tool-name');

                $toolNew->addVersion(new ToolVersion('tool-name', '1.0.0', null, null, null, null));

                return Diff::diff([], [], ['tool-name' => $toolOld], ['tool-name' => $toolNew]);
            })(),
        ];
        yield 'Only one version has been removed' => [
            'expected' => <<<EOF
                Remove version 1.0.0 of tool "tool-name"

                Changes in repository:
                  Changed tools:
                    Changes for tool-name:
                      Removed version 1.0.0

                EOF,
            'changes' => (static function () {
                $toolOld = new Tool('tool-name');
                $toolNew = new Tool('tool-name');

                $toolOld->addVersion(new ToolVersion('tool-name', '1.0.0', null, null, null, null));

                return Diff::diff([], [], ['tool-name' => $toolOld], ['tool-name' => $toolNew]);
            })(),
        ];
        yield 'Only one version has been changed' => [
            'expected' => <<<EOF
                Update version 1.0.0 of tool "tool-name"

                Changes in repository:
                  Changed tools:
                    Changes for tool-name:
                      Changed version 1.0.0:
                        url:
                          + changed

                EOF,
            'changes' => (static function () {
                $toolOld = new Tool('tool-name');
                $toolNew = new Tool('tool-name');

                $toolOld->addVersion(new ToolVersion('tool-name', '1.0.0', null, null, null, null));
                $toolNew->addVersion(new ToolVersion('tool-name', '1.0.0', 'changed', null, null, null));

                return Diff::diff([], [], ['tool-name' => $toolOld], ['tool-name' => $toolNew]);
            })(),
        ];
        yield 'Multiple changes have happened for one tool' => [
            'expected' => <<<EOF
                Update tool "tool-name": 3 new versions, 1 versions deleted, 2 versions changed

                Changes in repository:
                  Changed tools:
                    Changes for tool-name:
                      Added version 1.0.0
                      Added version 1.0.1
                      Added version 1.0.2
                      Removed version 1.0.3
                      Changed version 2.0.0:
                        url:
                          + changed
                      Changed version 2.0.1:
                        url:
                          + changed

                EOF,
            'changes' => (static function () {
                $toolOld = new Tool('tool-name');
                $toolNew = new Tool('tool-name');

                // 3 new versions:
                $toolNew->addVersion(new ToolVersion('tool-name', '1.0.0', 'changed', null, null, null));
                $toolNew->addVersion(new ToolVersion('tool-name', '1.0.1', 'changed', null, null, null));
                $toolNew->addVersion(new ToolVersion('tool-name', '1.0.2', 'changed', null, null, null));

                // 1 version deleted:
                $toolOld->addVersion(new ToolVersion('tool-name', '1.0.3', null, null, null, null));

                // 2 versions changed:
                $toolOld->addVersion(new ToolVersion('tool-name', '2.0.0', null, null, null, null));
                $toolNew->addVersion(new ToolVersion('tool-name', '2.0.0', 'changed', null, null, null));
                $toolOld->addVersion(new ToolVersion('tool-name', '2.0.1', null, null, null, null));
                $toolNew->addVersion(new ToolVersion('tool-name', '2.0.1', 'changed', null, null, null));

                return Diff::diff([], [], ['tool-name' => $toolOld], ['tool-name' => $toolNew]);
            })(),
        ];
        yield 'Two tools changed' => [
            'expected' => <<<EOF
                Update versions of "tool-name-1", "tool-name-2"

                Changes in repository:
                  Changed tools:
                    Changes for tool-name-1:
                      Added version 1.0.0
                    Changes for tool-name-2:
                      Added version 1.0.0

                EOF,
            'changes' => (static function () {
                $tool1Old = new Tool('tool-name-1');
                $tool1New = new Tool('tool-name-1');
                $tool2Old = new Tool('tool-name-2');
                $tool2New = new Tool('tool-name-2');

                $tool1New->addVersion(new ToolVersion('tool-name-1', '1.0.0', null, null, null, null));
                $tool2New->addVersion(new ToolVersion('tool-name-2', '1.0.0', null, null, null, null));

                return Diff::diff(
                    [],
                    [],
                    ['tool-name-1' => $tool1Old, 'tool-name-2' => $tool2Old],
                    ['tool-name-1' => $tool1New, 'tool-name-2' => $tool2New]
                );
            })(),
        ];
        yield 'Up to 3 tools changed' => [
            'expected' => <<<EOF
                Update versions of "tool-name-1", "tool-name-2", "tool-name-3"

                Changes in repository:
                  Changed tools:
                    Changes for tool-name-1:
                      Added version 1.0.0
                    Changes for tool-name-2:
                      Added version 1.0.0
                    Changes for tool-name-3:
                      Added version 1.0.0

                EOF,
            'changes' => (static function () {
                $tool1Old = new Tool('tool-name-1');
                $tool1New = new Tool('tool-name-1');
                $tool2Old = new Tool('tool-name-2');
                $tool2New = new Tool('tool-name-2');
                $tool3Old = new Tool('tool-name-3');
                $tool3New = new Tool('tool-name-3');

                $tool1New->addVersion(new ToolVersion('tool-name-1', '1.0.0', null, null, null, null));
                $tool2New->addVersion(new ToolVersion('tool-name-2', '1.0.0', null, null, null, null));
                $tool3New->addVersion(new ToolVersion('tool-name-3', '1.0.0', null, null, null, null));

                return Diff::diff(
                    [],
                    [],
                    ['tool-name-1' => $tool1Old, 'tool-name-2' => $tool2Old, 'tool-name-3' => $tool3Old],
                    ['tool-name-1' => $tool1New, 'tool-name-2' => $tool2New, 'tool-name-3' => $tool3New]
                );
            })(),
        ];
        yield 'More than 3 tools changed' => [
            'expected' => <<<EOF
                Update versions of "tool-name-1", "tool-name-2" and 2 more

                Changes in repository:
                  Changed tools:
                    Changes for tool-name-1:
                      Added version 1.0.0
                    Changes for tool-name-2:
                      Added version 1.0.0
                    Changes for tool-name-3:
                      Added version 1.0.0
                    Changes for tool-name-4:
                      Added version 1.0.0

                EOF,
            'changes' => (static function () {
                $tool1Old = new Tool('tool-name-1');
                $tool1New = new Tool('tool-name-1');
                $tool2Old = new Tool('tool-name-2');
                $tool2New = new Tool('tool-name-2');
                $tool3Old = new Tool('tool-name-3');
                $tool3New = new Tool('tool-name-3');
                $tool4Old = new Tool('tool-name-4');
                $tool4New = new Tool('tool-name-4');

                $tool1New->addVersion(new ToolVersion('tool-name-1', '1.0.0', null, null, null, null));
                $tool2New->addVersion(new ToolVersion('tool-name-2', '1.0.0', null, null, null, null));
                $tool3New->addVersion(new ToolVersion('tool-name-3', '1.0.0', null, null, null, null));
                $tool4New->addVersion(new ToolVersion('tool-name-4', '1.0.0', null, null, null, null));

                return Diff::diff(
                    [],
                    [],
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
            })(),
        ];
    }

    #[DataProvider('asStringProvider')]
    public function testAsString(string $expected, Diff $changes): void
    {
        self::assertSame($expected, $changes->asString(''));
    }
}
