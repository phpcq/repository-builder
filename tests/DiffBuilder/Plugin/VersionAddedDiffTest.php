<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\DiffBuilder\Plugin;

use Phpcq\RepositoryBuilder\DiffBuilder\Plugin\PluginVersionAddedDiff;
use Phpcq\RepositoryBuilder\DiffBuilder\PropertyDifference;
use Phpcq\RepositoryBuilder\DiffBuilder\VersionAddedDiffTrait;
use Phpcq\RepositoryBuilder\DiffBuilder\VersionDiffTrait;
use Phpcq\RepositoryDefinition\Plugin\PluginHash;
use Phpcq\RepositoryDefinition\Plugin\PluginRequirements;
use Phpcq\RepositoryDefinition\VersionRequirement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(VersionAddedDiffTrait::class)]
#[CoversClass(VersionDiffTrait::class)]
#[CoversClass(PluginVersionAddedDiff::class)]
final class VersionAddedDiffTest extends TestCase
{
    use PluginDiffTrait;

    public static function versionProvider(): array
    {
        $newRequirements = new PluginRequirements();
        $newRequirements->getPhpRequirements()->add(new VersionRequirement('php', '^7.3'));
        $newRequirements->getToolRequirements()->add(new VersionRequirement('othertool', '^1.5'));
        $newRequirements->getPluginRequirements()->add(new VersionRequirement('peerplugin', '^1.0'));
        $newRequirements->getComposerRequirements()->add(new VersionRequirement('vendor/lib', '^42.0'));

        return [
            'create minimal unknown version' => [
                'expected' => [
                    PropertyDifference::added('api-version', '1.0.0'),
                    PropertyDifference::added('code', 'unknown'),
                    PropertyDifference::added('requirements', ''),
                    PropertyDifference::added('checksum', 'sha-1:new-checksum'),
                    PropertyDifference::added('signature', null),
                ],
                'version' => fn(VersionAddedDiffTest $test) => $test->mockPluginVersion(
                    'tool-name',
                    '1.0.0',
                    null,
                    PluginHash::create('sha-1', 'new-checksum'),
                ),
            ],
            'create unknown version' => [
                'expected' => [
                    PropertyDifference::added('api-version', '1.0.0'),
                    PropertyDifference::added('code', 'unknown'),
                    PropertyDifference::added(
                        'requirements',
                        'platform: php:^7.3, tool: othertool:^1.5, plugin: peerplugin:^1.0, composer: vendor/lib:^42.0'
                    ),
                    PropertyDifference::added('checksum', 'sha-1:new-checksum'),
                    PropertyDifference::added('signature', null),
                ],
                'version' => fn(VersionAddedDiffTest $test) => $test->mockPluginVersion(
                    'tool-name',
                    '1.0.0',
                    $newRequirements,
                    PluginHash::create('sha-1', 'new-checksum'),
                ),
            ],
            'create minimal file version' => [
                'expected' => [
                    PropertyDifference::added('api-version', '1.0.0'),
                    PropertyDifference::added('code', 'url:https://example.org/new.phar'),
                    PropertyDifference::added('requirements', ''),
                    PropertyDifference::added('checksum', 'sha-1:new-checksum'),
                    PropertyDifference::added('signature', null),
                ],
                'version' => fn(VersionAddedDiffTest $test) => $test->mockPhpFilePluginVersionInterface(
                    'tool-name',
                    '1.0.0',
                    'https://example.org/new.phar',
                    null,
                    PluginHash::create('sha-1', 'new-checksum'),
                    null,
                ),
            ],
            'create signed file version' => [
                'expected' => [
                    PropertyDifference::added('api-version', '1.0.0'),
                    PropertyDifference::added('code', 'url:https://example.org/new.phar'),
                    PropertyDifference::added(
                        'requirements',
                        'platform: php:^7.3, tool: othertool:^1.5, plugin: peerplugin:^1.0, composer: vendor/lib:^42.0'
                    ),
                    PropertyDifference::added('checksum', 'sha-1:new-checksum'),
                    PropertyDifference::added('signature', 'url:https://example.org/new.phar.asc'),
                ],
                'version' => fn(VersionAddedDiffTest $test) => $test->mockPhpFilePluginVersionInterface(
                    'tool-name',
                    '1.0.0',
                    'https://example.org/new.phar',
                    $newRequirements,
                    PluginHash::create('sha-1', 'new-checksum'),
                    'https://example.org/new.phar.asc',
                ),
            ],
        ];
    }

    #[DataProvider('versionProvider')]
    public function testCreation(array $expected, callable $version): void
    {
        $this->assertInstanceOf(PluginVersionAddedDiff::class, $diff = PluginVersionAddedDiff::diff($version($this)));

        $this->assertSame(
            <<<EOF
            Added version 1.0.0

            EOF,
            $diff->__toString()
        );
        $this->assertSame('tool-name', $diff->getName());
        $this->assertSame('1.0.0', $diff->getVersion());
        $this->assertEquals($expected, $diff->getDifferences());
    }
}
