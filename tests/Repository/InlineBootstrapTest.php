<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\Repository;

use Phpcq\RepositoryBuilder\Repository\InlineBootstrap;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @covers \Phpcq\RepositoryBuilder\Repository\InlineBootstrap
 */
class InlineBootstrapTest extends TestCase
{
    public function testGetters(): void
    {
        $instance = new InlineBootstrap('1.0.0', 'code');
        $this->assertSame('1.0.0', $instance->getPluginVersion());
        $this->assertSame('code', $instance->getCode());
    }

    public function testThrowsForInvalidVersion(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid version string: 11.0.0');
        new InlineBootstrap('11.0.0', 'code');
    }
}
