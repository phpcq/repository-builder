<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\SourceProvider;

/**
 * @psalm-immutable
 */
final class LoaderContext
{
    private ?string $toolName = null;
    private ?string $toolConstraint = null;
    private ?string $pluginName = null;
    private ?string $pluginConstraint = null;
    private RepositoryLoader $loader;

    private function __construct(RepositoryLoader $loader)
    {
        $this->loader = $loader;
    }

    public static function create(RepositoryLoader $loader): LoaderContext
    {
        return new self($loader);
    }

    public function withTool(string $toolName, ?string $toolConstraint): LoaderContext
    {
        $clone = clone $this;
        $clone->toolName = $toolName;
        $clone->toolConstraint = $toolConstraint;

        return $clone;
    }

    public function withoutTool(): LoaderContext
    {
        $clone = clone $this;
        $clone->toolName = null;
        $clone->toolConstraint = null;

        return $clone;
    }

    public function getToolName(): ?string
    {
        return $this->toolName;
    }

    public function getToolConstraint(): ?string
    {
        return $this->toolConstraint;
    }

    public function withPlugin(string $pluginName, ?string $pluginConstraint): LoaderContext
    {
        $clone = clone $this;
        $clone->pluginName = $pluginName;
        $clone->pluginConstraint = $pluginConstraint;

        return $clone;
    }

    public function withoutPlugin(): LoaderContext
    {
        $clone = clone $this;
        $clone->pluginName = null;
        $clone->pluginConstraint = null;

        return $clone;
    }

    public function getPluginName(): ?string
    {
        return $this->pluginName;
    }

    public function getPluginConstraint(): ?string
    {
        return $this->pluginConstraint;
    }

    public function getLoader(): RepositoryLoader
    {
        return $this->loader;
    }
}
