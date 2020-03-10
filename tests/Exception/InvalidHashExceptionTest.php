<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\Exception;

use Phpcq\RepositoryBuilder\Exception\InvalidHashException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\RepositoryBuilder\Exception\InvalidHashException
 */
class InvalidHashExceptionTest extends TestCase
{
    public function testInitialization(): void
    {
        $exception = new InvalidHashException('unknown', 'hash-value');
        $this->assertSame('unknown', $exception->getHashType());
        $this->assertSame('hash-value', $exception->getHashValue());
        $this->assertSame('Invalid hash type: unknown (hash-value)', $exception->getMessage());
    }
}
