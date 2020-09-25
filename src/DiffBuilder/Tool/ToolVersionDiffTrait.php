<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\DiffBuilder\Tool;

use Phpcq\RepositoryBuilder\DiffBuilder\VersionDiffTrait;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;
use Phpcq\RepositoryDefinition\VersionRequirementList;

trait ToolVersionDiffTrait
{
    use VersionDiffTrait;

    private static function reqToStr(ToolVersionInterface $toolVersion): string
    {
        $requirements = $toolVersion->getRequirements();
        $result       = [];

        if (null !== $list = self::reqListToStr($requirements->getPhpRequirements())) {
            $result[] = 'platform: ' . $list;
        }

        if (null !== $list = self::reqListToStr($requirements->getComposerRequirements())) {
            $result[] = 'composer: ' . $list;
        }

        return implode(', ', $result);
    }

    private static function reqListToStr(VersionRequirementList $list): ?string
    {
        $result = [];
        foreach ($list as $requirement) {
            $result[] = $requirement->getName() . ':' . $requirement->getConstraint();
        }

        if ([] === $result) {
            return null;
        }

        return implode(', ', $result);
    }

    private static function hashToStr(ToolVersionInterface $toolVersion): string
    {
        $hash = $toolVersion->getHash();
        if (null === $hash) {
            return '';
        }

        return $hash->getType() . ':' . $hash->getValue();
    }
}
