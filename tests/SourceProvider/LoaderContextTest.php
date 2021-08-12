<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\SourceProvider;

use Phpcq\RepositoryBuilder\SourceProvider\LoaderContext;
use Phpcq\RepositoryBuilder\SourceProvider\RepositoryLoader;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/** @covers \Phpcq\RepositoryBuilder\SourceProvider\LoaderContext */
class LoaderContextTest extends TestCase
{
    public function testCreate(): void
    {
        $loader  = new RepositoryLoader($this->getMockForAbstractClass(ContainerInterface::class));
        $context = LoaderContext::create($loader);

        self::assertSame($loader, $context->getLoader(), 'Should set loader');
        self::assertNull($context->getPluginConstraint(), 'Should be null');
        self::assertNull($context->getPluginName(), 'Should be null');
        self::assertNull($context->getToolConstraint(), 'Should be null');
        self::assertNull($context->getToolName(), 'Should be null');
    }

    public function testWithTool(): void
    {
        $loader      = new RepositoryLoader($this->getMockForAbstractClass(ContainerInterface::class));
        $baseContext = LoaderContext::create($loader);
        $context     = $baseContext->withTool('super-tool', '^1.0');

        self::assertNotSame($baseContext, $context->getLoader(), 'Should be immutable.');
        self::assertSame($loader, $context->getLoader(), 'Should inherit loader');
        self::assertNull($context->getPluginConstraint(), 'Should be null');
        self::assertNull($context->getPluginName(), 'Should be null');
        self::assertSame('^1.0', $context->getToolConstraint());
        self::assertSame('super-tool', $context->getToolName());
    }

    public function testWithOutTool(): void
    {
        $loader      = new RepositoryLoader($this->getMockForAbstractClass(ContainerInterface::class));
        $baseContext = LoaderContext::create($loader)->withTool('super-tool', '^1.0');
        $context     = $baseContext->withoutTool();

        self::assertNotSame($baseContext, $context->getLoader(), 'Should be immutable.');
        self::assertSame($loader, $context->getLoader(), 'Should inherit loader');
        self::assertNull($context->getPluginConstraint(), 'Should be null');
        self::assertNull($context->getPluginName(), 'Should be null');
        self::assertNull($context->getToolConstraint(), 'Should be null');
        self::assertNull($context->getToolName(), 'Should be null');
    }

    public function testWithPlugin(): void
    {
        $loader      = new RepositoryLoader($this->getMockForAbstractClass(ContainerInterface::class));
        $baseContext = LoaderContext::create($loader);
        $context     = $baseContext->withPlugin('super-plugin', '^2.0');

        self::assertNotSame($baseContext, $context->getLoader(), 'Should be immutable.');
        self::assertSame($loader, $context->getLoader(), 'Should inherit loader');
        self::assertSame('^2.0', $context->getPluginConstraint());
        self::assertSame('super-plugin', $context->getPluginName());
        self::assertNull($context->getToolConstraint(), 'Should be null');
        self::assertNull($context->getToolName(), 'Should be null');
    }

    public function testWithOutPlugin(): void
    {
        $loader      = new RepositoryLoader($this->getMockForAbstractClass(ContainerInterface::class));
        $baseContext = LoaderContext::create($loader)->withPlugin('super-plugin', '^2.0');
        $context     = $baseContext->withoutPlugin();

        self::assertNotSame($baseContext, $context->getLoader(), 'Should be immutable.');
        self::assertSame($loader, $context->getLoader(), 'Should inherit loader');
        self::assertNull($context->getPluginConstraint(), 'Should be null');
        self::assertNull($context->getPluginName(), 'Should be null');
        self::assertNull($context->getToolConstraint(), 'Should be null');
        self::assertNull($context->getToolName(), 'Should be null');
    }
}
