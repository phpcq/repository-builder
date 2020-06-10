<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\Repository;

use Phpcq\RepositoryBuilder\Exception\InvalidHashException;
use Phpcq\RepositoryBuilder\Repository\BootstrapHash;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\RepositoryBuilder\Repository\BootstrapHash
 */
class BootstrapHashTest extends TestCase
{
    public function hashProvider(): array
    {
        return [
            'SHA_1' => [BootstrapHash::SHA_1, 'hash-value'],
            'SHA_256' => [BootstrapHash::SHA_256, 'hash-value'],
            'SHA_384' => [BootstrapHash::SHA_384, 'hash-value'],
            'SHA_512' => [BootstrapHash::SHA_512, 'hash-value'],
        ];
    }

    /**
     * @dataProvider hashProvider
     */
    public function testToolInitializesHash(string $hashType, string $hashValue): void
    {
        $hash = new BootstrapHash($hashType, $hashValue);
        // Would throw if not created.
        $this->assertSame($hashType, $hash->getType());
        $this->assertSame($hashValue, $hash->getValue());
    }

    public function testThrowsForInvalidHashType(): void
    {
        $this->expectException(InvalidHashException::class);
        $this->expectExceptionMessage('Invalid hash type: unknown-type (hash-value)');

        new BootstrapHash('unknown-type', 'hash-value');
    }
}
