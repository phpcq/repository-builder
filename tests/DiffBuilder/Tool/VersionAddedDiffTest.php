<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\DiffBuilder\Tool;

use Phpcq\RepositoryBuilder\DiffBuilder\PropertyDifference;
use Phpcq\RepositoryBuilder\DiffBuilder\Tool\ToolVersionAddedDiff;
use Phpcq\RepositoryDefinition\Tool\ToolHash;
use Phpcq\RepositoryDefinition\Tool\ToolRequirements;
use Phpcq\RepositoryDefinition\Tool\ToolVersion;
use Phpcq\RepositoryDefinition\VersionRequirement;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\RepositoryBuilder\DiffBuilder\VersionAddedDiffTrait
 * @covers \Phpcq\RepositoryBuilder\DiffBuilder\VersionDiffTrait
 * @covers \Phpcq\RepositoryBuilder\DiffBuilder\Tool\ToolVersionAddedDiff
 */
final class VersionAddedDiffTest extends TestCase
{
    public function testCreation(): void
    {
        $newRequirements = new ToolRequirements();
        $newRequirements->getPhpRequirements()->add(new VersionRequirement('php', '^7.3'));
        $newRequirements->getComposerRequirements()->add(new VersionRequirement('vendor/lib', '^42.0'));

        $newVersion = new ToolVersion(
            'tool-name',
            '1.0.0',
            'https://example.org/old.phar',
            $newRequirements,
            ToolHash::create('sha-1', 'old-checksum'),
            'https://example.org/old.phar.asc',
        );

        $this->assertInstanceOf(ToolVersionAddedDiff::class, $diff = ToolVersionAddedDiff::diff($newVersion));

        $this->assertSame(
            <<<EOF
            Added version 1.0.0

            EOF,
            $diff->__toString()
        );
        $this->assertSame('tool-name', $diff->getName());
        $this->assertSame('1.0.0', $diff->getVersion());
        $this->assertEquals([
            PropertyDifference::added('phar-url', 'https://example.org/old.phar'),
            PropertyDifference::added('requirements', 'platform: php:^7.3, composer: vendor/lib:^42.0'),
            PropertyDifference::added('checksum', 'sha-1:old-checksum'),
            PropertyDifference::added('signature', 'https://example.org/old.phar.asc'),
        ], $diff->getDifferences());
    }
}
