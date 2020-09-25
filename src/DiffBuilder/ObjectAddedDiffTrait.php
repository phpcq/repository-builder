<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\DiffBuilder;

trait ObjectAddedDiffTrait
{
    use ObjectDiffTrait;

    public function asString(string $prefix): string
    {
        if (empty($this->differences)) {
            return '';
        }

        $result = [];
        foreach ($this->differences as $difference) {
            $result[] = $difference->asString($prefix . '  ');
        }

        return $prefix . 'Added ' . $this->name . ':' . "\n" . implode('', $result);
    }
}
