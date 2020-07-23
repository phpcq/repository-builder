<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Repository\Plugin;

use Phpcq\RepositoryBuilder\Repository\VersionRequirement;
use Phpcq\RepositoryBuilder\Repository\VersionRequirementList;
use RuntimeException;

abstract class AbstractPluginVersion implements PluginVersionInterface
{
    private string $name;
    private string $version;
    private string $apiVersion;
    private PluginHash $hash;
    private PluginRequirements $requirements;

    public function __construct(
        string $name,
        string $version,
        string $apiVersion,
        ?PluginRequirements $requirements,
        PluginHash $hash
    ) {
        if ($apiVersion !== '1.0.0') {
            throw new RuntimeException('Invalid version string: ' . $apiVersion);
        }
        $this->name         = $name;
        $this->version      = $version;
        $this->apiVersion   = $apiVersion;
        $this->hash         = $hash;
        $this->requirements = $requirements ?? new PluginRequirements();
    }

    public function getApiVersion(): string
    {
        return '1.0.0';
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getHash(): PluginHash
    {
        return $this->hash;
    }

    public function getRequirements(): PluginRequirements
    {
        return $this->requirements;
    }

    public function merge(PluginVersionInterface $other): void
    {
        $otherRequirements = $other->getRequirements();
        foreach (
            [
                [$this->requirements->getPhpRequirements(), $otherRequirements->getPhpRequirements()],
                [$this->requirements->getToolRequirements(), $otherRequirements->getToolRequirements()],
                [$this->requirements->getPluginRequirements(), $otherRequirements->getPluginRequirements()],
                [$this->requirements->getComposerRequirements(), $otherRequirements->getComposerRequirements()],
            ] as $lists
        ) {
            /** @var VersionRequirementList[] $lists */
            $target = $lists[0];
            $source = $lists[1];
            foreach ($source as $requirement) {
                if (!$target->has($requirement->getName())) {
                    $target->add(new VersionRequirement(
                        $requirement->getName(),
                        $requirement->getConstraint()
                    ));
                }
            }
        }
    }
}
