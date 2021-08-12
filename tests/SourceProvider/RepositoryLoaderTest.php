<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\SourceProvider;

use InvalidArgumentException;
use Phpcq\RepositoryBuilder\SourceProvider\LoaderContext;
use Phpcq\RepositoryBuilder\SourceProvider\RepositoryLoader;
use Phpcq\RepositoryBuilder\SourceProvider\SourceRepositoryFactoryInterface;
use Phpcq\RepositoryBuilder\SourceProvider\SourceRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/** @covers \Phpcq\RepositoryBuilder\SourceProvider\RepositoryLoader */
final class RepositoryLoaderTest extends TestCase
{
    public function testLoadingWorksCorrectly(): void
    {
        $configuration = ['type' => 'test', 'configuration'];
        $factories     = $this->getMockForAbstractClass(ContainerInterface::class);
        $loader        = new RepositoryLoader($factories);
        $context       = LoaderContext::create($loader);
        $factory       = $this->getMockForAbstractClass(SourceRepositoryFactoryInterface::class);
        $result        = $this->getMockForAbstractClass(SourceRepositoryInterface::class);
        $factory->expects(self::once())->method('create')->with($configuration, $context)->willReturn($result);

        $factories->expects(self::once())->method('has')->with('test')->willReturn(true);
        $factories->expects(self::once())->method('get')->with('test')->willReturn($factory);

        self::assertSame($result, $loader->load($configuration, $context));
    }

    public function testLoadingThrowsForUnknownTypes(): void
    {
        $configuration = ['type' => 'test', 'configuration'];
        $factories     = $this->getMockForAbstractClass(ContainerInterface::class);
        $loader        = new RepositoryLoader($factories);
        $context       = LoaderContext::create($loader);

        $factories->expects(self::once())->method('has')->with('test')->willReturn(false);
        $factories->expects(self::never())->method('get');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown repository type: test');

        $loader->load($configuration, $context);
    }
}
