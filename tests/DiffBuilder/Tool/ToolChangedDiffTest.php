<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\DiffBuilder\Tool;

use Phpcq\RepositoryBuilder\DiffBuilder\Tool\ToolChangedDiff;
use Phpcq\RepositoryDefinition\Tool\Tool;
use Phpcq\RepositoryDefinition\Tool\ToolHash;
use Phpcq\RepositoryDefinition\Tool\ToolRequirements;
use Phpcq\RepositoryDefinition\Tool\ToolVersion;
use Phpcq\RepositoryDefinition\VersionRequirement;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\RepositoryBuilder\DiffBuilder\ObjectChangedDiffTrait
 * @covers \Phpcq\RepositoryBuilder\DiffBuilder\ObjectDiffTrait
 * @covers \Phpcq\RepositoryBuilder\DiffBuilder\Tool\ToolChangedDiff
 */
final class ToolChangedDiffTest extends TestCase
{
    use ToolDiffTrait;

    public function testAddsNewVersionsCorrectly(): void
    {
        $old = $this->mockToolWithVersions('tool-name', []);
        $new = $this->mockToolWithVersions('tool-name', ['1.0.0', '2.0.0']);

        $this->assertInstanceOf(ToolChangedDiff::class, $diff = ToolChangedDiff::diff($old, $new));
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
        $old = $this->mockToolWithVersions('tool-name', ['1.0.0', '2.0.0']);
        $new = $this->mockToolWithVersions('tool-name', []);

        $this->assertInstanceOf(ToolChangedDiff::class, $diff = ToolChangedDiff::diff($old, $new));
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
        $old = $this->mockToolWithVersions('tool-name', ['1.0.0', '2.0.0']);
        $new = $this->mockToolWithVersions('tool-name', ['1.0.0', '2.0.0']);

        $this->assertNull(ToolChangedDiff::diff($old, $new));
    }

    public function testAddsChangedVersionCorrectly(): void
    {
        $old = new Tool('tool-name');
        $new = new Tool('tool-name');

        $oldRequirements = new ToolRequirements();
        $oldRequirements->getPhpRequirements()->add(new VersionRequirement('php', '^7.3'));
        $oldVersion = new ToolVersion(
            'tool-name',
            '1.0.0',
            'https://example.org/old.phar',
            $oldRequirements,
            ToolHash::create('sha-1', 'old-hash'),
            'https://example.org/old.phar.asc',
        );

        $newRequirements = new ToolRequirements();
        $newRequirements->getPhpRequirements()->add(new VersionRequirement('php', '^7.4'));
        $newVersion = new ToolVersion(
            'tool-name',
            '1.0.0',
            'https://example.org/new.phar',
            $newRequirements,
            ToolHash::create('sha-512', 'new-hash'),
            'https://example.org/new.phar.asc',
        );

        $old->addVersion($oldVersion);
        $new->addVersion($newVersion);

        $this->assertInstanceOf(ToolChangedDiff::class, $diff = ToolChangedDiff::diff($old, $new));
        $this->assertSame(
            <<<EOF
            Changes for tool-name:
              Changed version 1.0.0:
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

            EOF,
            $diff->__toString()
        );
        $this->assertSame('tool-name', $diff->getName());
    }
}
