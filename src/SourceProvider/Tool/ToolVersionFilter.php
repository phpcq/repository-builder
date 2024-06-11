<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\SourceProvider\Tool;

use Composer\Semver\Semver;
use UnexpectedValueException;

final class ToolVersionFilter
{
    public const string WILDCARD_CONSTRAINT = '*';
    public const string NEVER_MATCHING_CONSTRAINT = '>0 <0';

    private string $toolName;
    private string $allowedVersions;
    private ?ToolVersionFilter $previous;

    public function __construct(string $toolName, string $allowedVersions, ?ToolVersionFilter $previous = null)
    {
        $this->toolName        = $toolName;
        $this->allowedVersions = $allowedVersions;
        $this->previous        = $previous;
    }

    public function getToolName(): string
    {
        return $this->toolName;
    }

    public function accepts(string $version): bool
    {
        if ($this->previous && !$this->previous->accepts($version)) {
            return false;
        }

        try {
            return Semver::satisfies($version, $this->allowedVersions);
        } catch (UnexpectedValueException $exception) {
            // Ignore versions not adhering to semver.
            return false;
        }
    }
}
