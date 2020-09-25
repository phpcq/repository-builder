<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\DiffBuilder\Plugin;

use Phpcq\RepositoryBuilder\DiffBuilder\Plugin\PluginChangedDiff;
use Phpcq\RepositoryDefinition\Plugin\Plugin;
use Phpcq\RepositoryDefinition\Plugin\PluginHash;
use Phpcq\RepositoryDefinition\Plugin\PluginRequirements;
use Phpcq\RepositoryDefinition\VersionRequirement;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\RepositoryBuilder\DiffBuilder\ObjectChangedDiffTrait
 * @covers \Phpcq\RepositoryBuilder\DiffBuilder\ObjectDiffTrait
 * @covers \Phpcq\RepositoryBuilder\DiffBuilder\Plugin\PluginChangedDiff
 */
final class PluginChangedDiffTest extends TestCase
{
    use PluginDiffTrait;

    public function testAddsNewVersionsCorrectly(): void
    {
        $old = $this->mockPluginWithVersions('tool-name', []);
        $new = $this->mockPluginWithVersions('tool-name', ['1.0.0', '2.0.0']);

        $this->assertInstanceOf(PluginChangedDiff::class, $diff = PluginChangedDiff::diff($old, $new));
        $this->assertSame(
            <<<EOF
            Changes for tool-name:
              Added version 1.0.0
              Added version 2.0.0

            EOF,
            $diff->__toString()
        );
        $this->assertSame('tool-name', $diff->getName());
    }

    public function testAddsRemovedVersionsCorrectly(): void
    {
        $old = $this->mockPluginWithVersions('tool-name', ['1.0.0', '2.0.0']);
        $new = $this->mockPluginWithVersions('tool-name', []);

        $this->assertInstanceOf(PluginChangedDiff::class, $diff = PluginChangedDiff::diff($old, $new));
        $this->assertSame(
            <<<EOF
            Changes for tool-name:
              Removed version 1.0.0
              Removed version 2.0.0

            EOF,
            $diff->__toString()
        );
        $this->assertSame('tool-name', $diff->getName());
    }

    public function testReturnsNullOnNochanges(): void
    {
        $old = $this->mockPluginWithVersions('tool-name', ['1.0.0', '2.0.0']);
        $new = $this->mockPluginWithVersions('tool-name', ['1.0.0', '2.0.0']);

        $this->assertNull(PluginChangedDiff::diff($old, $new));
    }

    public function testAddsChangedVersionCorrectly(): void
    {
        $old = new Plugin('tool-name');
        $new = new Plugin('tool-name');

        $oldRequirements = new PluginRequirements();
        $oldRequirements->getPhpRequirements()->add(new VersionRequirement('php', '^7.3'));
        $oldVersion = $this->mockPhpFilePluginVersionInterface(
            'tool-name',
            '1.0.0',
            'https://example.org/old.phar',
            $oldRequirements,
            PluginHash::create('sha-1', 'old-checksum'),
            'https://example.org/old.phar.asc',
        );

        $newRequirements = new PluginRequirements();
        $newRequirements->getPhpRequirements()->add(new VersionRequirement('php', '^7.4'));
        $newVersion = $this->mockPhpFilePluginVersionInterface(
            'tool-name',
            '1.0.0',
            'https://example.org/new.phar',
            $newRequirements,
            PluginHash::create('sha-512', 'new-checksum'),
            'https://example.org/new.phar.asc',
        );

        $old->addVersion($oldVersion);
        $new->addVersion($newVersion);

        $this->assertInstanceOf(PluginChangedDiff::class, $diff = PluginChangedDiff::diff($old, $new));
        $this->assertSame(
            <<<EOF
            Changes for tool-name:
              Changed version 1.0.0:
                code:
                  - url:https://example.org/old.phar
                  + url:https://example.org/new.phar
                requirements:
                  - platform: php:^7.3
                  + platform: php:^7.4
                checksum:
                  - sha-1:old-checksum
                  + sha-512:new-checksum
                signature:
                  - url:https://example.org/old.phar.asc
                  + url:https://example.org/new.phar.asc

            EOF,
            $diff->__toString()
        );
        $this->assertSame('tool-name', $diff->getName());
    }
}
