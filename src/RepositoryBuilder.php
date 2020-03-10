<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder;

use Phpcq\RepositoryBuilder\Repository\Tool;
use Phpcq\RepositoryBuilder\Repository\ToolVersion;

class RepositoryBuilder
{
    private array $versionProviders;

    private array $enrichingProviders;

    private JsonRepositoryWriter $writer;

    private array $tools;

    /**
     * Create a new instance.
     *
     * @param array                $versionProviders
     * @param array                $enrichingProviders
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
        $this->tools = [];
        foreach ($this->versionProviders as $versionProvider) {
            foreach ($versionProvider->getIterator() as $version) {
                $toolName = $version->getName();
                if (!isset($this->tools[$toolName])) {
                    $this->tools[$toolName] = new Tool($toolName);
                }
                $this->enrichVersion($version);
                $this->tools[$version->getName()]->addVersion($version);
            }
        }

        foreach ($this->tools as $tool) {
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