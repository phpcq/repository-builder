<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder;

use Phpcq\RepositoryBuilder\Repository\Tool;
use Phpcq\RepositoryBuilder\Repository\ToolVersion;
use Phpcq\RepositoryBuilder\SourceProvider\EnrichingRepositoryInterface;
use Phpcq\RepositoryBuilder\SourceProvider\VersionProvidingRepositoryInterface;

class RepositoryBuilder
{
    /**
     * @var VersionProvidingRepositoryInterface[]
     */
    private array $versionProviders;

    /**
     * @var EnrichingRepositoryInterface[]
     */
    private array $enrichingProviders;

    private JsonRepositoryWriter $writer;

    /**
     * Create a new instance.
     *
     * @param VersionProvidingRepositoryInterface[] $versionProviders
     * @param EnrichingRepositoryInterface[]        $enrichingProviders
     * @param JsonRepositoryWriter $writer
     */
    public function __construct(array $versionProviders, array $enrichingProviders, JsonRepositoryWriter $writer)
    {
        $this->versionProviders   = $versionProviders;
        $this->enrichingProviders = $enrichingProviders;
        $this->writer             = $writer;
    }

    public function build(): void
    {
        foreach ($this->enrichingProviders as $enrichingProvider) {
            if (!$enrichingProvider->isFresh()) {
                $enrichingProvider->refresh();
            }
        }

        /** @var Tool[] $tools */
        $tools = [];
        foreach ($this->versionProviders as $versionProvider) {
            foreach ($versionProvider->getIterator() as $version) {
                $toolName = $version->getName();
                if (!isset($tools[$toolName])) {
                    $tools[$toolName] = new Tool($toolName);
                }
                $tool = $tools[$version->getName()];

                if ($tool->has($version->getVersion())) {
                    $other = $tool->getVersion($version->getVersion());
                    $other->merge($version);
                    continue;
                }

                $this->enrichVersion($version);
                $tool->addVersion($version);
            }
        }

        foreach ($tools as $tool) {
            $this->writer->write($tool);
        }

        $this->writer->save();
    }

    private function enrichVersion(ToolVersion $version): void
    {
        foreach ($this->enrichingProviders as $enrichingProvider) {
            if ($enrichingProvider->supports($version)) {
                $enrichingProvider->enrich($version);
            }
        }
    }
}
