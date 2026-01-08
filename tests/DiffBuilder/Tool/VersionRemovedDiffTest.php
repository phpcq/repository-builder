<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\DiffBuilder\Tool;

use Phpcq\RepositoryBuilder\DiffBuilder\PropertyDifference;
use Phpcq\RepositoryBuilder\DiffBuilder\Tool\ToolVersionRemovedDiff;
use Phpcq\RepositoryBuilder\DiffBuilder\VersionDiffTrait;
use Phpcq\RepositoryBuilder\DiffBuilder\VersionRemovedDiffTrait;
use Phpcq\RepositoryDefinition\Tool\ToolHash;
use Phpcq\RepositoryDefinition\Tool\ToolRequirements;
use Phpcq\RepositoryDefinition\Tool\ToolVersion;
use Phpcq\RepositoryDefinition\VersionRequirement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(VersionDiffTrait::class)]
#[CoversClass(VersionRemovedDiffTrait::class)]
#[CoversClass(ToolVersionRemovedDiff::class)]
final class VersionRemovedDiffTest extends TestCase
{
    public function testCreation(): void
    {
        $requirements = new ToolRequirements();
        $requirements->getPhpRequirements()->add(new VersionRequirement('php', '^7.3'));
        $oldVersion = new ToolVersion(
            'tool-name',
            '1.0.0',
            'https://example.org/old.phar',
            $requirements,
            ToolHash::create('sha-1', 'old-checksum'),
            'https://example.org/old.phar.asc',
        );

        $this->assertInstanceOf(ToolVersionRemovedDiff::class, $diff = ToolVersionRemovedDiff::diff($oldVersion));

        $this->assertSame(
            <<<EOF
            Removed version 1.0.0

            EOF,
            $diff->__toString()
        );
        $this->assertSame('tool-name', $diff->getName());
        $this->assertSame('1.0.0', $diff->getVersion());
        $this->assertEquals([
            PropertyDifference::removed('phar-url', 'https://example.org/old.phar'),
            PropertyDifference::removed('requirements', 'platform: php:^7.3'),
            PropertyDifference::removed('checksum', 'sha-1:old-checksum'),
            PropertyDifference::removed('signature', 'https://example.org/old.phar.asc'),
        ], $diff->getDifferences());
    }
}
