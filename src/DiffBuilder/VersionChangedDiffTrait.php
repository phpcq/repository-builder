<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\DiffBuilder;

trait VersionChangedDiffTrait
{
    use VersionDiffTrait;

    public function asString(string $prefix): string
    {
        $result = [$prefix . 'Changed version ' . $this->version . ':'];
        foreach ($this->getDifferences() as $difference) {
            $result[] = $prefix . '  ' . $difference->getName() . ':';
            if (null !== ($oldValue = $difference->getOldValue())) {
                $result[] = $prefix . '  ' . '  - ' . $oldValue;
            }
            if (null !== ($newValue = $difference->getNewValue())) {
                $result[] = $prefix . '  ' . '  + ' . $newValue;
            }
        }
        $result[] = '';

        return implode("\n", $result);
    }
}
