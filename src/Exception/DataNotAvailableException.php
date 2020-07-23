<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Exception;

use RuntimeException;

/**
 * @psalm-type TDataNotAvailableExceptionSerialized = array{
 *   message: string,
 *   code: int,
 *   file: string,
 *   line: int,
 * }
 */
class DataNotAvailableException extends RuntimeException
{
    /** @psalm-return TDataNotAvailableExceptionSerialized */
    public function __serialize(): array
    {
        return [
            'message' => $this->message,
            'code'    => $this->code,
            'file'    => $this->file,
            'line'    => $this->line,
        ];
    }

    /** @psalm-param TDataNotAvailableExceptionSerialized $data */
    public function __unserialize(array $data): void
    {
        $this->message = $data['message'];
        $this->code    = $data['code'];
        $this->file    = $data['file'];
        $this->line    = $data['line'];
    }
}
