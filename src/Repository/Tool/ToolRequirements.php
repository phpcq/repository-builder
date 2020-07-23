<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Repository\Tool;

use Phpcq\RepositoryBuilder\Repository\VersionRequirementList;

class ToolRequirements
{
    /**
     * Platform requirements.
     */
    private VersionRequirementList $phpRequirements;

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
     * Retrieve composerRequirements.
     *
     * @return VersionRequirementList
     */
    public function getComposerRequirements()
    {
        return $this->composerRequirements;
    }
}
