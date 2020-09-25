<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\DiffBuilder\Tool;

use Phpcq\RepositoryBuilder\DiffBuilder\Tool\ToolVersionChangedDiff;
use Phpcq\RepositoryDefinition\Tool\ToolHash;
use Phpcq\RepositoryDefinition\Tool\ToolRequirements;
use Phpcq\RepositoryDefinition\Tool\ToolVersion;
use Phpcq\RepositoryDefinition\VersionRequirement;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\RepositoryBuilder\DiffBuilder\VersionChangedDiffTrait
 * @covers \Phpcq\RepositoryBuilder\DiffBuilder\VersionDiffTrait
 * @covers \Phpcq\RepositoryBuilder\DiffBuilder\Tool\ToolVersionChangedDiff
 */
final class VersionChangedDiffTest extends TestCase
{
    public function testIgnoresEmptyDiff(): void
    {
        $requirements = new ToolRequirements();
        $requirements->getPhpRequirements()->add(new VersionRequirement('php', '^7.3'));

        $oldVersion = new ToolVersion(
            'tool-name',
            '1.0.0',
            'https://example.org/old.phar',
            $requirements,
            ToolHash::create('sha-1', 'old-hash'),
            'https://example.org/old.phar.asc',
        );

        $this->assertNull(ToolVersionChangedDiff::diff($oldVersion, $oldVersion));
    }

    /** @SuppressWarnings(PHPMD.ExcessiveMethodLength) */
    public function compareTestProvider(): array
    {
        $requirementsOld = new ToolRequirements();
        $requirementsOld->getPhpRequirements()->add(new VersionRequirement('php', '^7.3'));
        $requirementsNew = new ToolRequirements();
        $requirementsNew->getPhpRequirements()->add(new VersionRequirement('php', '^7.4'));

        return [
            'Change url' => [
                'expected' => <<<EOF
                Changed version 1.0.0:
                  url:
                    - https://example.org/old.phar
                    + https://example.org/new.phar

                EOF,
                'old' => new ToolVersion(
                    'tool-name',
                    '1.0.0',
                    'https://example.org/old.phar',
                    new ToolRequirements(),
                    null,
                    null,
                ),
                'new' => new ToolVersion(
                    'tool-name',
                    '1.0.0',
                    'https://example.org/new.phar',
                    new ToolRequirements(),
                    null,
                    null,
                ),
            ],
            'Change requirements' => [
                'expected' => <<<EOF
                Changed version 1.0.0:
                  requirements:
                    - platform: php:^7.3
                    + platform: php:^7.4

                EOF,
                'old' => new ToolVersion(
                    'tool-name',
                    '1.0.0',
                    null,
                    $requirementsOld,
                    null,
                    null,
                ),
                'new' => new ToolVersion(
                    'tool-name',
                    '1.0.0',
                    null,
                    $requirementsNew,
                    null,
                    null,
                ),
            ],
            'Change hash' => [
                'expected' => <<<EOF
                Changed version 1.0.0:
                  checksum:
                    - sha-1:old-hash
                    + sha-512:new-hash

                EOF,
                'old' => new ToolVersion(
                    'tool-name',
                    '1.0.0',
                    null,
                    new ToolRequirements(),
                    ToolHash::create('sha-1', 'old-hash'),
                    null,
                ),
                'new' => new ToolVersion(
                    'tool-name',
                    '1.0.0',
                    null,
                    new ToolRequirements(),
                    ToolHash::create('sha-512', 'new-hash'),
                    null,
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
                    new ToolRequirements(),
                    null,
                    'https://example.org/old.phar.asc',
                ),
                'new' => new ToolVersion(
                    'tool-name',
                    '1.0.0',
                    null,
                    new ToolRequirements(),
                    null,
                    'https://example.org/new.phar.asc',
                ),
            ],
        ];
    }

    /**
     * @dataProvider compareTestProvider
     */
    public function testProcessesCorrectly(string $expected, ToolVersion $oldVersion, ToolVersion $newVersion): void
    {
        $this->assertInstanceOf(
            ToolVersionChangedDiff::class,
            $diff = ToolVersionChangedDiff::diff($oldVersion, $newVersion)
        );
        $this->assertSame('tool-name', $diff->getName());
        $this->assertSame('1.0.0', $diff->getVersion());

        $this->assertSame($expected, $diff->__toString());
    }
}
