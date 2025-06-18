<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\SourceProvider\Tool;

use Phpcq\RepositoryBuilder\SourceProvider\LoaderContext;
use Phpcq\RepositoryBuilder\SourceProvider\SourceRepositoryFactoryInterface;
use Phpcq\RepositoryBuilder\SourceProvider\SourceRepositoryInterface;
use Phpcq\RepositoryDefinition\Tool\ToolRequirements;
use Phpcq\RepositoryDefinition\Tool\ToolVersion;
use Phpcq\RepositoryDefinition\VersionRequirement;

/**
 * @psalm-type TStaticValuesRepositoryConfigurationToolRequirements=array{
 *   php: array<string, string>,
 *   composer: array<string, string>
 * }
 * @psalm-type TStaticValuesRepositoryConfigurationTool=array{
 *   version: string,
 *   requirements: TStaticValuesRepositoryConfigurationToolRequirements
 * }
 * @psalm-type TStaticValuesRepositoryConfigurationTools=array<string, list<TStaticValuesRepositoryConfigurationTool>>
 * @psalm-type TStaticValuesRepositoryConfiguration=array{tools: TStaticValuesRepositoryConfigurationTools}
 * @implements SourceRepositoryFactoryInterface<TStaticValuesRepositoryConfiguration>
 */
class StaticValuesProviderRepositoryFactory implements SourceRepositoryFactoryInterface
{
    public function create(array $configuration, LoaderContext $context): SourceRepositoryInterface
    {
        return new StaticValuesProviderRepository(...$this->convertTools($configuration['tools']));
    }

    /**
     * @psalm-param TStaticValuesRepositoryConfigurationTools $tools
     * @return list<ToolVersion>
     */
    private function convertTools(array $tools): array
    {
        $result = [];
        foreach ($tools as $name => $toolVersions) {
            foreach ($toolVersions as $version) {
                $result[] = new ToolVersion(
                    $name,
                    $version['version'],
                    null,
                    $this->convertToolRequirements($version['requirements']),
                    null,
                    null,
                );
            }
        }
        return $result;
    }

    /** @psalm-param TStaticValuesRepositoryConfigurationToolRequirements $requirements */
    private function convertToolRequirements(array $requirements): ToolRequirements
    {
        $requirementList = new ToolRequirements();
        if ([] !== ($php = $requirements['php'] ?? [])) {
            $versionRequirements = $requirementList->getPhpRequirements();
            foreach ($php as $name => $constraint) {
                $versionRequirements->add(new VersionRequirement($name, $constraint));
            }
        }
        if ([] !== ($composer = $requirements['composer'] ?? [])) {
            $versionRequirements = $requirementList->getComposerRequirements();
            foreach ($composer as $name => $constraint) {
                $versionRequirements->add(new VersionRequirement($name, $constraint));
            }
        }

        return $requirementList;
    }
}
