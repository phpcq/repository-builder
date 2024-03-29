<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test;

use InvalidArgumentException;
use Phpcq\RepositoryBuilder\JsonRepositoryWriter;
use Phpcq\RepositoryBuilder\RepositoryBuilder;
use Phpcq\RepositoryBuilder\SourceProvider\CompoundRepository;
use Phpcq\RepositoryBuilder\SourceProvider\SourceRepositoryInterface;
use Phpcq\RepositoryBuilder\SourceProvider\Tool\ToolVersionEnrichingRepositoryInterface;
use Phpcq\RepositoryBuilder\SourceProvider\Tool\ToolVersionProvidingRepositoryInterface;
use Phpcq\RepositoryDefinition\Tool\Tool;
use Phpcq\RepositoryDefinition\Tool\ToolVersion;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\RepositoryBuilder\RepositoryBuilder
 */
final class RepositoryBuilderTest extends TestCase
{
    public function testBuild(): void
    {
        $versionProvider1 = $this->createMock(ToolVersionProvidingRepositoryInterface::class);
        $versionProvider2 = $this->createMock(ToolVersionProvidingRepositoryInterface::class);

        $version11 = $this->createMock(ToolVersion::class);
        $version12 = $this->createMock(ToolVersion::class);
        $version21 = $this->createMock(ToolVersion::class);
        $version22 = $this->createMock(ToolVersion::class);

        $version11->method('getName')->willReturn('version1');
        $version12->method('getName')->willReturn('version1');
        $version21->method('getName')->willReturn('version2');
        $version22->method('getName')->willReturn('version2');

        $version11->method('getVersion')->willReturn('1.0.0');
        $version12->method('getVersion')->willReturn('2.0.0');
        $version21->method('getVersion')->willReturn('1.0.0');
        $version22->method('getVersion')->willReturn('2.0.0');

        $versionProvider1
            ->expects($this->once())
            ->method('getToolIterator')
            ->willReturnCallback(function () use ($version11, $version12) {
                yield $version11;
                yield $version12;
            });

        $versionProvider2
            ->expects($this->once())
            ->method('getToolIterator')
            ->willReturnCallback(function () use ($version21, $version22) {
                yield $version21;
                yield $version22;
            });

        $enrichingProvider1 = $this->createMock(ToolVersionEnrichingRepositoryInterface::class);
        $enrichingProvider2 = $this->createMock(ToolVersionEnrichingRepositoryInterface::class);

        $enrichingProvider1
            ->expects(self::exactly(4))
            ->method('supports')
            ->withConsecutive(
                [$version11],
                [$version12],
                [$version21],
                [$version22],
            )
            ->willReturnOnConsecutiveCalls(true, false, true, false);
        $enrichingProvider1
            ->expects(self::exactly(2))
            ->method('enrich')
            ->withConsecutive([$version11], [$version21]);
        $enrichingProvider2
            ->expects(self::exactly(4))
            ->method('supports')
            ->withConsecutive(
                [$version11],
                [$version12],
                [$version21],
                [$version22],
            )
            ->willReturnOnConsecutiveCalls(false, true, false, true);
        $enrichingProvider2
            ->expects(self::exactly(2))
            ->method('enrich')
            ->withConsecutive([$version21], [$version22]);

        $writer  = $this->createMock(JsonRepositoryWriter::class);
        $builder = new RepositoryBuilder(
            new CompoundRepository(
                $versionProvider1,
                $versionProvider2,
                $enrichingProvider1,
                $enrichingProvider2
            ),
            $writer
        );

        $names = ['version1', 'version2'];
        $writer
            ->expects($this->exactly(2))
            ->method('writeTool')
            ->willReturnOnConsecutiveCalls()
            ->willReturnCallback(function (Tool $tool) use (&$names) {
                $this->assertSame(current($names), $tool->getName());
                next($names);
            });

        $builder->build();
    }

    public function testMultiPurposeProvider(): void
    {
        $writer  = $this->createMock(JsonRepositoryWriter::class);
        $provider = $this->getMockForAbstractClass(ToolVersionProvidingAndEnrichingRepositoryInterface::class);
        $builder = new RepositoryBuilder(new CompoundRepository($provider), $writer);

        $version = $this->createMock(ToolVersionInterface::class);

        $provider->expects($this->once())
            ->method('getToolIterator')
            ->willReturnCallback(function () use ($version) {
                yield $version;
            });

        $provider->expects($this->once())->method('supports')->willReturn(true);
        $provider->expects($this->once())->method('enrich');

        $builder->build();
    }

    public function testThrowForUnsupportedRepositoryProvider(): void
    {
        $writer  = $this->createMock(JsonRepositoryWriter::class);
        $provider = $this->getMockForAbstractClass(SourceRepositoryInterface::class);

        $this->expectException(InvalidArgumentException::class);
        new RepositoryBuilder(new CompoundRepository($provider), $writer);
    }
}
