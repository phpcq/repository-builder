<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\SourceProvider\Tool;

use Phpcq\RepositoryBuilder\SourceProvider\Tool\ToolVersionFilter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ToolVersionFilter::class)]
final class ToolVersionFilterTest extends TestCase
{
    public function testToolNameGetterReturnsToolName(): void
    {
        $filter = new ToolVersionFilter('tool', ToolVersionFilter::WILDCARD_CONSTRAINT);
        $this->assertSame('tool', $filter->getToolName());
    }

    public static function versionProvider(): array
    {
        return [
            [
                'expected'   => true,
                'constraint' => ToolVersionFilter::WILDCARD_CONSTRAINT,
                'version'    => 'dev-master',
            ],
            [
                'expected'   => true,
                'constraint' => '^1',
                'version'    => '1.0.5',
            ],
            [
                'expected'   => false,
                'constraint' => '^2',
                'version'    => '1.0.0',
            ],
            [
                'expected'   => true,
                'constraint' => '^1 | ^2',
                'version'    => '2.0.0',
            ],
        ];
    }

    #[DataProvider('versionProvider')]
    public function testFiltersVersion(bool $expected, string $constraint, string $version): void
    {
        $filter = new ToolVersionFilter('tool', $constraint);
        $this->assertSame($expected, $filter->accepts($version));
    }

    public function testRejectsInvalidVersion(): void
    {
        $filter = new ToolVersionFilter('tool', '*');
        $this->assertFalse($filter->accepts('unparsable'));
    }

    public function testRejectsVersionIfPreviousRejectsVersion(): void
    {
        $previous = new ToolVersionFilter('tool', ToolVersionFilter::NEVER_MATCHING_CONSTRAINT);

        $filter = new ToolVersionFilter('tool', ToolVersionFilter::WILDCARD_CONSTRAINT, $previous);
        $this->assertFalse($filter->accepts('1.0'));
    }
}
