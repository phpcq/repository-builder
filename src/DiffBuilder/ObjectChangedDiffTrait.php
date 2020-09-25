<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\DiffBuilder;

trait ObjectChangedDiffTrait
{
    use ObjectDiffTrait;

    public function asString(string $prefix): string
    {
        $result = [];
        foreach ($this->getDifferences() as $difference) {
            $result[] = $difference->asString($prefix . '  ');
        }

        return $prefix . 'Changes for ' . $this->name . ':' . "\n" . implode('', $result);
    }
}
