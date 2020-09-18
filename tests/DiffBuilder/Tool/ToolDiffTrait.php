<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\DiffBuilder\Tool;

use Phpcq\RepositoryDefinition\Tool\Tool;
use Phpcq\RepositoryDefinition\Tool\ToolHash;
use Phpcq\RepositoryDefinition\Tool\ToolRequirements;
use Phpcq\RepositoryDefinition\Tool\ToolVersion;
use Phpcq\RepositoryDefinition\VersionRequirement;

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
