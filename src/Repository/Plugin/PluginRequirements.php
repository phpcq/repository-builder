<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Repository\Plugin;

use Phpcq\RepositoryBuilder\Repository\VersionRequirementList;

class PluginRequirements
{
    /**
     * Platform requirements.
     */
    private VersionRequirementList $phpRequirements;

    /**
     * Required tools.
     */
    private VersionRequirementList $toolRequirements;

    /**
     * Required peer plugins.
     */
    private VersionRequirementList $pluginRequirements;

    /**
     * Required composer libraries.
     */
    private VersionRequirementList $composerRequirements;

    /**
     * Create a new instance.
     */
    public function __construct()
    {
        $this->phpRequirements = new VersionRequirementList();
        $this->toolRequirements = new VersionRequirementList();
        $this->pluginRequirements = new VersionRequirementList();
        $this->composerRequirements = new VersionRequirementList();
    }

    /**
     * Retrieve phpRequirements.
     *
     * @return VersionRequirementList
     */
    public function getPhpRequirements()
    {
        return $this->phpRequirements;
    }

    /**
     * Retrieve toolRequirements.
     *
     * @return VersionRequirementList
     */
    public function getToolRequirements()
    {
        return $this->toolRequirements;
    }

    /**
     * Retrieve pluginRequirements.
     *
     * @return VersionRequirementList
     */
    public function getPluginRequirements()
    {
        return $this->pluginRequirements;
    }

    /**
     * Retrieve composerRequirements.
     *
     * @return VersionRequirementList
     */
    public function getComposerRequirements()
    {
        return $this->composerRequirements;
    }
}
