<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder;

use InvalidArgumentException;
use Phpcq\RepositoryBuilder\SourceProvider\PluginVersionProviderRepositoryInterface;
use Phpcq\RepositoryBuilder\SourceProvider\SourceRepositoryInterface;
use Phpcq\RepositoryBuilder\SourceProvider\ToolVersionEnrichingRepositoryInterface;
use Phpcq\RepositoryBuilder\SourceProvider\ToolVersionProvidingRepositoryInterface;
use Phpcq\RepositoryDefinition\Plugin\Plugin;
use Phpcq\RepositoryDefinition\Repository;
use Phpcq\RepositoryDefinition\Tool\Tool;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;

class RepositoryBuilder
{
    /**
     * @var ToolVersionProvidingRepositoryInterface[]
     */
    private array $toolProviders = [];

    /**
     * @var ToolVersionEnrichingRepositoryInterface[]
     */
    private array $enrichingProviders = [];

    /**
     * @var PluginVersionProviderRepositoryInterface[]
     */
    private array $pluginProviders = [];

    private JsonRepositoryWriter $writer;

    /**
     * Create a new instance.
     *
     * @param SourceRepositoryInterface[] $providers
     * @param JsonRepositoryWriter        $writer
     */
    public function __construct(array $providers, JsonRepositoryWriter $writer)
    {
        foreach ($providers as $provider) {
            $supported = false;
            if ($provider instanceof PluginVersionProviderRepositoryInterface) {
                $supported = true;
                $this->pluginProviders[] = $provider;
            } else {
                if ($provider instanceof ToolVersionProvidingRepositoryInterface) {
                    $supported = true;
                    $this->toolProviders[] = $provider;
                }
                if ($provider instanceof ToolVersionEnrichingRepositoryInterface) {
                    $supported = true;
                    $this->enrichingProviders[] = $provider;
                }
            }

            if (!$supported) {
                throw new InvalidArgumentException('Unknown provider type ' . get_class($provider));
            }
        }

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
        foreach ($this->enrichingProviders as $enrichingProvider) {
            if (!$enrichingProvider->isFresh()) {
                $enrichingProvider->refresh();
            }
        }

        foreach ($this->toolProviders as $versionProvider) {
            foreach ($versionProvider->getIterator() as $version) {
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

                $this->enrichVersion($version);
                $tool->addVersion($version);
            }
        }
    }

    private function collectPlugins(Repository $repository): void
    {
        foreach ($this->pluginProviders as $versionProvider) {
            foreach ($versionProvider->getIterator() as $version) {
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

    private function enrichVersion(ToolVersionInterface $version): void
    {
        foreach ($this->enrichingProviders as $enrichingProvider) {
            if ($enrichingProvider->supports($version)) {
                $enrichingProvider->enrich($version);
            }
        }
    }
}
