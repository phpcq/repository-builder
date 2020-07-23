<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\Repository\Plugin;

use Phpcq\RepositoryBuilder\Repository\Plugin\PhpFilePluginVersion;
use Phpcq\RepositoryBuilder\Repository\Plugin\PluginHash;
use Phpcq\RepositoryBuilder\Repository\Plugin\PluginRequirements;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @covers \Phpcq\RepositoryBuilder\Repository\Plugin\AbstractPluginVersion
 * @covers \Phpcq\RepositoryBuilder\Repository\Plugin\PhpFilePluginVersion
 */
class PhpFilePluginVersionTest extends TestCase
{
    public function testGetters(): void
    {
        $instance = new PhpFilePluginVersion(
            'plugin-a',
            '2.0.0',
            '1.0.0',
            $requirements = new PluginRequirements(),
            __FILE__,
            null
        );
        $this->assertSame('plugin-a', $instance->getName());
        $this->assertSame('2.0.0', $instance->getVersion());
        $this->assertSame('1.0.0', $instance->getApiVersion());
        $this->assertSame($requirements, $instance->getRequirements());
        $this->assertSame(file_get_contents(__FILE__), $instance->getCode());
        $this->assertNull($instance->getSignature());
        $this->assertSame(__FILE__, $instance->getFilePath());
        $this->assertInstanceOf(PluginHash::class, $instance->getHash());
    }

    public function testGetsSignatureFromFile(): void
    {
        $instance = new PhpFilePluginVersion(
            'plugin-a',
            '2.0.0',
            '1.0.0',
            new PluginRequirements(),
            __FILE__,
            __FILE__,
        );

        $this->assertSame(file_get_contents(__FILE__), $instance->getSignature());
    }

    public function testThrowsForInvalidVersion(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid version string: 11.0.0');
        new PhpFilePluginVersion(
            'plugin-a',
            '2.0.0',
            '11.0.0',
            new PluginRequirements(),
            __FILE__,
            null
        );
    }
}
