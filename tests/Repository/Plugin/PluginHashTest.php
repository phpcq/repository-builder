<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\Repository\Plugin;

use Phpcq\RepositoryBuilder\Exception\InvalidHashException;
use Phpcq\RepositoryBuilder\Repository\Plugin\PluginHash;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\RepositoryBuilder\Repository\Plugin\PluginHash
 */
class PluginHashTest extends TestCase
{
    public function hashProvider(): array
    {
        return [
            'SHA_1' => [PluginHash::SHA_1, 'hash-value'],
            'SHA_256' => [PluginHash::SHA_256, 'hash-value'],
            'SHA_384' => [PluginHash::SHA_384, 'hash-value'],
            'SHA_512' => [PluginHash::SHA_512, 'hash-value'],
        ];
    }

    /**
     * @dataProvider hashProvider
     */
    public function testToolInitializesHash(string $hashType, string $hashValue): void
    {
        $hash = PluginHash::create($hashType, $hashValue);
        // Would throw if not created.
        $this->assertSame($hashType, $hash->getType());
        $this->assertSame($hashValue, $hash->getValue());
    }

    public function testThrowsForInvalidHashType(): void
    {
        $this->expectException(InvalidHashException::class);
        $this->expectExceptionMessage('Invalid hash type: unknown-type (hash-value)');

        PluginHash::create('unknown-type', 'hash-value');
    }
}
