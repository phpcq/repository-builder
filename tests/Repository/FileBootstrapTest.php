<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\Repository;

use Phpcq\RepositoryBuilder\Repository\BootstrapHash;
use Phpcq\RepositoryBuilder\Repository\FileBootstrap;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @covers \Phpcq\RepositoryBuilder\Repository\FileBootstrap
 */
class FileBootstrapTest extends TestCase
{
    public function testGetters(): void
    {
        $instance = new FileBootstrap('1.0.0', __FILE__, new BootstrapHash(BootstrapHash::SHA_512, 'foo'));
        $this->assertSame('1.0.0', $instance->getPluginVersion());
        $this->assertSame(file_get_contents(__FILE__), $instance->getCode());
        $this->assertSame(__FILE__, $instance->getFilePath());
    }

    public function testThrowsForInvalidVersion(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid version string: 11.0.0');
        new FileBootstrap('11.0.0', 'code', new BootstrapHash(BootstrapHash::SHA_512, 'foo'));
    }
}
