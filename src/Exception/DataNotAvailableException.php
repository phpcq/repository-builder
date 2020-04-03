<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Exception;

use RuntimeException;

class DataNotAvailableException extends RuntimeException
{
    public function __serialize(): array
    {
        return [
            'message' => $this->message,
            'code'    => $this->code,
            'file'    => $this->file,
            'line'    => $this->line,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->message = $data['message'];
        $this->code    = $data['code'];
        $this->file    = $data['file'];
        $this->line    = $data['line'];
    }
}
