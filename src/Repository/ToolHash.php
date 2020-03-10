<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Repository;

use Phpcq\RepositoryBuilder\Exception\InvalidHashException;

class ToolHash
{
    public const SHA_1   = 'sha-1';
    public const SHA_256 = 'sha-256';
    public const SHA_384 = 'sha-384';
    public const SHA_512 = 'sha-512';

    private string $type;
    private string $value;

    /**
     * @throws InvalidHashException When the hash type is unknown.
     */
    public function __construct(string $type, string $value)
    {
        if (!in_array($type, [self::SHA_1, self::SHA_256, self::SHA_384, self::SHA_512])) {
            throw new InvalidHashException($type, $value);
        }

        $this->type  = $type;
        $this->value = $value;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
