<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\SourceProvider;

use Generator;
use InvalidArgumentException;
use Phpcq\RepositoryBuilder\SourceProvider\Tool\ToolVersionEnrichingRepositoryInterface;
use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

final class CompoundRepository implements
    SourceRepositoryInterface,
    ToolVersionProvidingRepositoryInterface,
    ToolVersionEnrichingRepositoryInterface,
    PluginVersionProvidingRepositoryInterface,
    LoggerAwareInterface
{
    /**
     * @var list<ToolVersionProvidingRepositoryInterface>
     */
    private array $toolProviders = [];

    /**
     * @var list<ToolVersionEnrichingRepositoryInterface>
     */
    private array $enrichingProviders = [];

    /**
     * @var list<PluginVersionProvidingRepositoryInterface>
     */
    private array $pluginProviders = [];

    public function __construct(SourceRepositoryInterface ...$providers)
    {
        foreach ($providers as $provider) {
            $supported = false;
            if ($provider instanceof PluginVersionProvidingRepositoryInterface) {
                $supported = true;
                $this->pluginProviders[] = $provider;
            }
            if ($provider instanceof ToolVersionProvidingRepositoryInterface) {
                $supported = true;
                $this->toolProviders[] = $provider;
            }
            if ($provider instanceof ToolVersionEnrichingRepositoryInterface) {
                $supported = true;
                $this->enrichingProviders[] = $provider;
            }

            if (!$supported) {
                throw new InvalidArgumentException('Unknown provider type ' . get_class($provider));
            }
        }
    }

    public function isFresh(): bool
    {
        foreach ($this->toolProviders as $toolProvider) {
            if (!$toolProvider->isFresh()) {
                return false;
            }
        }
        foreach ($this->enrichingProviders as $enrichingProvider) {
            if (!$enrichingProvider->isFresh()) {
                return false;
            }
        }
        foreach ($this->pluginProviders as $pluginProvider) {
            if (!$pluginProvider->isFresh()) {
                return false;
            }
        }

        return true;
    }

    public function refresh(): void
    {
        foreach ($this->toolProviders as $toolProvider) {
            $toolProvider->refresh();
        }
        foreach ($this->enrichingProviders as $enrichingProvider) {
            $enrichingProvider->refresh();
        }
        foreach ($this->pluginProviders as $pluginProvider) {
            $pluginProvider->refresh();
        }
    }

    public function supports(ToolVersionInterface $version): bool
    {
        foreach ($this->enrichingProviders as $enrichingProvider) {
            if ($enrichingProvider->supports($version)) {
                return true;
            }
        }

        return false;
    }

    public function enrich(ToolVersionInterface $version): void
    {
        foreach ($this->enrichingProviders as $enrichingProvider) {
            if ($enrichingProvider->supports($version)) {
                $enrichingProvider->enrich($version);
            }
        }
    }

    /**
     * @return Generator<int, ToolVersionInterface>
     */
    public function getToolIterator(): Generator
    {
        foreach ($this->toolProviders as $toolProvider) {
            foreach ($toolProvider->getToolIterator() as $toolVersion) {
                $this->enrich($toolVersion);

                yield $toolVersion;
            }
        }
    }

    /**
     * @return Generator<int, PluginVersionInterface>
     */
    public function getPluginIterator(): Generator
    {
        foreach ($this->pluginProviders as $pluginProvider) {
            foreach ($pluginProvider->getPluginIterator() as $pluginVersion) {
                yield $pluginVersion;
            }
        }
    }

    public function setLogger(LoggerInterface $logger)
    {
        foreach ($this->toolProviders as $toolProvider) {
            if ($toolProvider instanceof LoggerAwareInterface) {
                $toolProvider->setLogger($logger);
            }
        }
        foreach ($this->enrichingProviders as $enrichingProvider) {
            if ($enrichingProvider instanceof LoggerAwareInterface) {
                $enrichingProvider->setLogger($logger);
            }
        }
        foreach ($this->pluginProviders as $pluginProvider) {
            if ($pluginProvider instanceof LoggerAwareInterface) {
                $pluginProvider->setLogger($logger);
            }
        }
    }
}
