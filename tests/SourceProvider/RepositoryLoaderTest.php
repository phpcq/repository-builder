<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\SourceProvider;

use InvalidArgumentException;
use Phpcq\RepositoryBuilder\SourceProvider\LoaderContext;
use Phpcq\RepositoryBuilder\SourceProvider\RepositoryLoader;
use Phpcq\RepositoryBuilder\SourceProvider\SourceRepositoryFactoryInterface;
use Phpcq\RepositoryBuilder\SourceProvider\SourceRepositoryInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

#[CoversClass(RepositoryLoader::class)]
final class RepositoryLoaderTest extends TestCase
{
    public function testLoadingWorksCorrectly(): void
    {
        $configuration = ['type' => 'test', 'configuration'];
        $factories     = $this->getMockBuilder(ContainerInterface::class)->getMock();
        $loader        = new RepositoryLoader($factories);
        $context       = LoaderContext::create($loader);
        $factory       = $this->getMockBuilder(SourceRepositoryFactoryInterface::class)->getMock();
        $result        = $this->getMockBuilder(SourceRepositoryInterface::class)->getMock();
        $factory->expects($this->once())->method('create')->with($configuration, $context)->willReturn($result);

        $factories->expects($this->once())->method('has')->with('test')->willReturn(true);
        $factories->expects($this->once())->method('get')->with('test')->willReturn($factory);

        self::assertSame($result, $loader->load($configuration, $context));
    }

    public function testLoadingThrowsForUnknownTypes(): void
    {
        $configuration = ['type' => 'test', 'configuration'];
        $factories     = $this->getMockBuilder(ContainerInterface::class)->getMock();
        $loader        = new RepositoryLoader($factories);
        $context       = LoaderContext::create($loader);

        $factories->expects($this->once())->method('has')->with('test')->willReturn(false);
        $factories->expects($this->never())->method('get');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown repository type: test');

        $loader->load($configuration, $context);
    }
}
