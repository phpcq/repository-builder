<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Repository;

use Phpcq\RepositoryBuilder\Exception\InvalidHashException;

use function hash;
use function hash_file;
use function in_array;

/**
 * Abstract base for hashes.
 */
abstract class AbstractHash
{
    private const HASHMAP = [
        self::SHA_1   => 'sha1',
        self::SHA_256 => 'sha256',
        self::SHA_384 => 'sha384',
        self::SHA_512 => 'sha512',
    ];

    public const SHA_1 = 'sha-1';
    public const SHA_256 = 'sha-256';
    public const SHA_384 = 'sha-384';
    public const SHA_512 = 'sha-512';

    private string $type;

    private string $value;

    public function getType(): string
    {
        return $this->type;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    /** @return static */
    public static function create(string $type, string $value): self
    {
        return new static($type, $value);
    }

    /** @return static */
    public static function createForFile(string $absolutePath, string $type = self::SHA_512): self
    {
        /** @psalm-var array<self::SHA_1|self::SHA_256|self::SHA_384|self::SHA_512, string> $hashMap */
        static $hashMap = [
            self::SHA_1   => 'sha1',
            self::SHA_256 => 'sha256',
            self::SHA_384 => 'sha384',
            self::SHA_512 => 'sha512',
        ];

        return static::create($type, hash_file($hashMap[$type], $absolutePath));
    }

    /** @return static */
    public static function createForString(string $contents, string $type = self::SHA_512): self
    {
        return static::create($type, hash(self::HASHMAP[$type], $contents));
    }

    /**
     * @throws InvalidHashException When the hash type is unknown.
     */
    private function __construct(string $type, string $value)
    {
        if (!in_array($type, [self::SHA_1, self::SHA_256, self::SHA_384, self::SHA_512])) {
            throw new InvalidHashException($type, $value);
        }

        $this->type  = $type;
        $this->value = $value;
    }
}
