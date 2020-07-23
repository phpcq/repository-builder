<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\DiffBuilder\Plugin;

use Phpcq\RepositoryBuilder\DiffBuilder\Plugin\PluginVersionChangedDiff;
use Phpcq\RepositoryBuilder\Repository\Plugin\PluginHash;
use Phpcq\RepositoryBuilder\Repository\Plugin\PluginRequirements;
use Phpcq\RepositoryBuilder\Repository\Plugin\PluginVersionInterface;
use Phpcq\RepositoryBuilder\Repository\VersionRequirement;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\RepositoryBuilder\DiffBuilder\VersionChangedDiffTrait
 * @covers \Phpcq\RepositoryBuilder\DiffBuilder\VersionDiffTrait
 * @covers \Phpcq\RepositoryBuilder\DiffBuilder\Plugin\PluginVersionChangedDiff
 */
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
            'code',
            $requirements,
            PluginHash::create('sha-1', 'old-hash'),
            'old phar asc',
        );

        $this->assertNull(PluginVersionChangedDiff::diff($oldVersion, $oldVersion));
    }

    public function compareTestProvider(): array
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
                'old' => $this->mockPluginVersion(
                    'tool-name',
                    '1.0.0',
                    'code',
                    null,
                    null,
                    'signature',
                    '1.0.0',
                ),
                'new' => $this->mockPluginVersion(
                    'tool-name',
                    '1.0.0',
                    'code',
                    null,
                    null,
                    'signature',
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
                'old' => $this->mockPhpFilePluginVersionInterface(
                    'tool-name',
                    '1.0.0',
                    'https://example.org/old.phar',
                    new PluginRequirements(),
                ),
                'new' => $this->mockPhpFilePluginVersionInterface(
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
                'old' => $this->mockPluginVersion(
                    'tool-name',
                    '1.0.0',
                    'code',
                    $requirementsOld,
                ),
                'new' => $this->mockPluginVersion(
                    'tool-name',
                    '1.0.0',
                    'code',
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
                'old' => $this->mockPluginVersion(
                    'tool-name',
                    '1.0.0',
                    'code',
                    null,
                    PluginHash::create('sha-1', 'old-checksum'),
                ),
                'new' => $this->mockPluginVersion(
                    'tool-name',
                    '1.0.0',
                    'code',
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
                'old' => $this->mockPhpFilePluginVersionInterface(
                    'tool-name',
                    '1.0.0',
                    'code',
                    null,
                    null,
                    null,
                ),
                'new' => $this->mockPhpFilePluginVersionInterface(
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
                'old' => $this->mockPhpFilePluginVersionInterface(
                    'tool-name',
                    '1.0.0',
                    'code',
                    null,
                    null,
                    'https://example.org/old.phar.asc',
                ),
                'new' => $this->mockPhpFilePluginVersionInterface(
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
                'old' => $this->mockPhpFilePluginVersionInterface(
                    'tool-name',
                    '1.0.0',
                    'code',
                    null,
                    null,
                    'https://example.org/old.phar.asc',
                ),
                'new' => $this->mockPhpFilePluginVersionInterface(
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

    /**
     * @dataProvider compareTestProvider
     */
    public function testProcessesCorrectly(
        string $expected,
        PluginVersionInterface $oldVersion,
        PluginVersionInterface $newVersion
    ): void {
        $this->assertInstanceOf(
            PluginVersionChangedDiff::class,
            $diff = PluginVersionChangedDiff::diff($oldVersion, $newVersion)
        );
        $this->assertSame('tool-name', $diff->getName());
        $this->assertSame('1.0.0', $diff->getVersion());

        $this->assertSame($expected, $diff->__toString());
    }
}
