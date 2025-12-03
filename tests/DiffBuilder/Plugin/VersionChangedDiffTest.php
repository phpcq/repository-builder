<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\DiffBuilder\Plugin;

use Phpcq\RepositoryBuilder\DiffBuilder\Plugin\PluginVersionChangedDiff;
use Phpcq\RepositoryBuilder\DiffBuilder\VersionChangedDiffTrait;
use Phpcq\RepositoryBuilder\DiffBuilder\VersionDiffTrait;
use Phpcq\RepositoryDefinition\Plugin\PluginHash;
use Phpcq\RepositoryDefinition\Plugin\PluginRequirements;
use Phpcq\RepositoryDefinition\VersionRequirement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(VersionChangedDiffTrait::class)]
#[CoversClass(VersionDiffTrait::class)]
#[CoversClass(PluginVersionChangedDiff::class)]
final class VersionChangedDiffTest extends TestCase
{
    use PluginDiffTrait;

    public function testIgnoresEmptyDiff(): void
    {
        $requirements = new PluginRequirements();
        $requirements->getPhpRequirements()->add(new VersionRequirement('php', '^7.3'));

        $oldVersion = $this->mockPluginVersion(
            'tool-name',
            '1.0.0',
            $requirements,
            PluginHash::create('sha-1', 'old-hash'),
        );

        $this->assertNull(PluginVersionChangedDiff::diff($oldVersion, $oldVersion));
    }

    /** @SuppressWarnings(PHPMD.ExcessiveMethodLength) */
    public static function compareTestProvider(): array
    {
        $requirementsOld = new PluginRequirements();
        $requirementsOld->getPhpRequirements()->add(new VersionRequirement('php', '^7.3'));
        $requirementsNew = new PluginRequirements();
        $requirementsNew->getPhpRequirements()->add(new VersionRequirement('php', '^7.4'));

        return [
            'Change api version' => [
                'expected' => <<<EOF
                Changed version 1.0.0:
                  api-version:
                    - 1.0.0
                    + 1.1.0

                EOF,
                'old' => fn(VersionChangedDiffTest $test) => $test->mockPluginVersion(
                    'tool-name',
                    '1.0.0',
                    null,
                    null,
                    '1.0.0',
                ),
                'new' => fn(VersionChangedDiffTest $test) => $test->mockPluginVersion(
                    'tool-name',
                    '1.0.0',
                    null,
                    null,
                    '1.1.0',
                ),
            ],
            'Change url' => [
                'expected' => <<<EOF
                Changed version 1.0.0:
                  code:
                    - url:https://example.org/old.phar
                    + url:https://example.org/new.phar

                EOF,
                'old' => fn(VersionChangedDiffTest $test) => $test->mockPhpFilePluginVersionInterface(
                    'tool-name',
                    '1.0.0',
                    'https://example.org/old.phar',
                    new PluginRequirements(),
                ),
                'new' => fn(VersionChangedDiffTest $test) => $test->mockPhpFilePluginVersionInterface(
                    'tool-name',
                    '1.0.0',
                    'https://example.org/new.phar',
                    new PluginRequirements(),
                ),
            ],
            'Change requirements' => [
                'expected' => <<<EOF
                Changed version 1.0.0:
                  requirements:
                    - platform: php:^7.3
                    + platform: php:^7.4

                EOF,
                'old' => fn(VersionChangedDiffTest $test) => $test->mockPluginVersion(
                    'tool-name',
                    '1.0.0',
                    $requirementsOld,
                ),
                'new' => fn(VersionChangedDiffTest $test) => $test->mockPluginVersion(
                    'tool-name',
                    '1.0.0',
                    $requirementsNew,
                ),
            ],
            'Change checksum' => [
                'expected' => <<<EOF
                Changed version 1.0.0:
                  checksum:
                    - sha-1:old-checksum
                    + sha-512:new-checksum

                EOF,
                'old' => fn(VersionChangedDiffTest $test) => $test->mockPluginVersion(
                    'tool-name',
                    '1.0.0',
                    null,
                    PluginHash::create('sha-1', 'old-checksum'),
                ),
                'new' => fn(VersionChangedDiffTest $test) => $test->mockPluginVersion(
                    'tool-name',
                    '1.0.0',
                    null,
                    PluginHash::create('sha-512', 'new-checksum'),
                ),
            ],
            'Add signature' => [
                'expected' => <<<EOF
                Changed version 1.0.0:
                  signature:
                    + url:https://example.org/new.phar.asc

                EOF,
                'old' => fn(VersionChangedDiffTest $test) => $test->mockPhpFilePluginVersionInterface(
                    'tool-name',
                    '1.0.0',
                    'code',
                    null,
                    null,
                    null,
                ),
                'new' => fn(VersionChangedDiffTest $test) => $test->mockPhpFilePluginVersionInterface(
                    'tool-name',
                    '1.0.0',
                    'code',
                    null,
                    null,
                    'https://example.org/new.phar.asc',
                ),
            ],
            'Change signature' => [
                'expected' => <<<EOF
                Changed version 1.0.0:
                  signature:
                    - url:https://example.org/old.phar.asc
                    + url:https://example.org/new.phar.asc

                EOF,
                'old' => fn(VersionChangedDiffTest $test) => $test->mockPhpFilePluginVersionInterface(
                    'tool-name',
                    '1.0.0',
                    'code',
                    null,
                    null,
                    'https://example.org/old.phar.asc',
                ),
                'new' => fn(VersionChangedDiffTest $test) => $test->mockPhpFilePluginVersionInterface(
                    'tool-name',
                    '1.0.0',
                    'code',
                    null,
                    null,
                    'https://example.org/new.phar.asc',
                ),
            ],
            'Remove signature' => [
                'expected' => <<<EOF
                Changed version 1.0.0:
                  signature:
                    - url:https://example.org/old.phar.asc

                EOF,
                'old' => fn(VersionChangedDiffTest $test) => $test->mockPhpFilePluginVersionInterface(
                    'tool-name',
                    '1.0.0',
                    'code',
                    null,
                    null,
                    'https://example.org/old.phar.asc',
                ),
                'new' => fn(VersionChangedDiffTest $test) => $test->mockPhpFilePluginVersionInterface(
                    'tool-name',
                    '1.0.0',
                    'code',
                    null,
                    null,
                    null
                ),
            ],
        ];
    }

    #[DataProvider('compareTestProvider')]
    public function testProcessesCorrectly(
        string $expected,
        callable $old,
        callable $new
    ): void {
        $diff = PluginVersionChangedDiff::diff($old($this), $new($this));
        $this->assertInstanceOf(PluginVersionChangedDiff::class, $diff);
        $this->assertSame('tool-name', $diff->getName());
        $this->assertSame('1.0.0', $diff->getVersion());

        $this->assertSame($expected, $diff->__toString());
    }
}
