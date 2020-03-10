<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Exception;

use RuntimeException;

class InvalidHashException extends RuntimeException
{
    private string $hashType;
    private string $hashValue;

    /**
     * Create a new instance.
     *
     * @param string $hashType
     * @param string $hashValue
     */
    public function __construct(string $hashType, string $hashValue)
    {
        $this->hashType  = $hashType;
        $this->hashValue = $hashValue;
        parent::__construct('Invalid hash type: ' . $hashType . ' (' . $hashValue . ')');
    }

    public function getHashType(): string
    {
        return $this->hashType;
    }

    public function getHashValue(): string
    {
        return $this->hashValue;
    }
}
