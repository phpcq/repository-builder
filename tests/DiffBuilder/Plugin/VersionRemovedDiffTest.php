<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\DiffBuilder\Plugin;

use Phpcq\RepositoryBuilder\DiffBuilder\Plugin\PluginVersionRemovedDiff;
use Phpcq\RepositoryBuilder\DiffBuilder\PropertyDifference;
use Phpcq\RepositoryDefinition\Plugin\PluginHash;
use Phpcq\RepositoryDefinition\Plugin\PluginRequirements;
use Phpcq\RepositoryDefinition\VersionRequirement;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\RepositoryBuilder\DiffBuilder\VersionDiffTrait
 * @covers \Phpcq\RepositoryBuilder\DiffBuilder\VersionRemovedDiffTrait
 * @covers \Phpcq\RepositoryBuilder\DiffBuilder\Plugin\PluginVersionRemovedDiff
 */
final class VersionRemovedDiffTest extends TestCase
{
    use PluginDiffTrait;

    public function testCreation(): void
    {
        $requirements = new PluginRequirements();
        $requirements->getPhpRequirements()->add(new VersionRequirement('php', '^7.3'));
        $oldVersion = $this->mockPhpFilePluginVersionInterface(
            'tool-name',
            '1.0.0',
            'https://example.org/old.phar',
            $requirements,
            PluginHash::create('sha-1', 'old-checksum'),
            'https://example.org/old.phar.asc',
        );

        $this->assertInstanceOf(PluginVersionRemovedDiff::class, $diff = PluginVersionRemovedDiff::diff($oldVersion));

        $this->assertSame(
            <<<EOF
            Removed version 1.0.0

            EOF,
            $diff->__toString()
        );
        $this->assertSame('tool-name', $diff->getName());
        $this->assertSame('1.0.0', $diff->getVersion());
        $this->assertEquals([
            PropertyDifference::removed('api-version', '1.0.0'),
            PropertyDifference::removed('code', 'url:https://example.org/old.phar'),
            PropertyDifference::removed('requirements', 'platform: php:^7.3'),
            PropertyDifference::removed('checksum', 'sha-1:old-checksum'),
            PropertyDifference::removed('signature', 'url:https://example.org/old.phar.asc'),
        ], $diff->getDifferences());
    }
}
