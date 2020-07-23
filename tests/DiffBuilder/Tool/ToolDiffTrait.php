<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\DiffBuilder\Tool;

use Phpcq\RepositoryBuilder\Repository\Tool\Tool;
use Phpcq\RepositoryBuilder\Repository\Tool\ToolHash;
use Phpcq\RepositoryBuilder\Repository\Tool\ToolRequirements;
use Phpcq\RepositoryBuilder\Repository\Tool\ToolVersion;
use Phpcq\RepositoryBuilder\Repository\VersionRequirement;

trait ToolDiffTrait
{
    private function mockToolWithVersions(string $toolName, array $versions): Tool
    {
        $tool = new Tool($toolName);
        foreach ($versions as $version) {
            $requirements = new ToolRequirements();
            $requirements->getPhpRequirements()->add(new VersionRequirement('php', '^7.4'));
            $tool->addVersion(new ToolVersion(
                $toolName,
                $version,
                'https://example.org/' . $toolName . '-' . $version . '.phar',
                $requirements,
                ToolHash::create('sha-512', $toolName . '-' . $version . '-hash'),
                'https://example.org/' . $toolName . '-' . $version . '.phar.asc',
            ));
        }

        return $tool;
    }
}
