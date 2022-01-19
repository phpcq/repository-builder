<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder;

use Phpcq\RepositoryBuilder\SourceProvider\CompoundRepository;
use Phpcq\RepositoryDefinition\Plugin\Plugin;
use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\RepositoryDefinition\Repository;
use Phpcq\RepositoryDefinition\Tool\Tool;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;

class RepositoryBuilder
{
    private CompoundRepository $providers;

    private JsonRepositoryWriter $writer;

    public function __construct(CompoundRepository $providers, JsonRepositoryWriter $writer)
    {
        $this->providers = $providers;
        $this->writer = $writer;
    }

    public function build(): void
    {
        $repository = new Repository();
        $this->collectTools($repository);
        $this->collectPlugins($repository);

        foreach ($repository->iterateTools() as $tool) {
            $this->writer->writeTool($tool);
        }

        foreach ($repository->iteratePlugins() as $plugin) {
            $this->writer->writePlugin($plugin);
        }

        $this->writer->save();
    }

    private function collectTools(Repository $repository): void
    {
        foreach ($this->providers->getToolIterator() as $version) {
            $toolName = $version->getName();
            if (!$repository->hasTool($toolName)) {
                $repository->addTool(new Tool($toolName));
            }
            $tool = $repository->getTool($toolName);

            if ($tool->has($version->getVersion())) {
                $other = $tool->getVersion($version->getVersion());
                $other->merge($version);
                continue;
            }

            $tool->addVersion($version);
        }
    }

    private function collectPlugins(Repository $repository): void
    {
        foreach ($this->providers->getPluginIterator() as $version) {
            $pluginName = $version->getName();
            if (!$repository->hasPlugin($pluginName)) {
                $repository->addPlugin(new Plugin($pluginName));
            }
            $plugin = $repository->getPlugin($pluginName);

            if ($plugin->has($version->getVersion())) {
                $other = $plugin->getVersion($version->getVersion());
                $other->merge($version);
                continue;
            }

            $plugin->addVersion($version);
        }
    }
}
