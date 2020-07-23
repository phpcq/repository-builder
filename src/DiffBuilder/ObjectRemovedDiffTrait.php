<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\DiffBuilder;

trait ObjectRemovedDiffTrait
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

        return $prefix . 'Removed ' . $this->name . ':' . "\n" . implode('', $result);
    }
}
