<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\SourceProvider;

use Generator;
use Phpcq\RepositoryBuilder\Util\StringUtil;
use Phpcq\RepositoryDefinition\Plugin\PhpFilePluginVersion;
use Phpcq\RepositoryDefinition\Plugin\PluginHash;
use Phpcq\RepositoryDefinition\Plugin\PluginRequirements;
use Phpcq\RepositoryDefinition\VersionRequirement;
use RuntimeException;

/**
 * @psalm-type TPluginCatalogEntry = array{
 *   constraint: string,
 *   file: string,
 *   plugin-version: string,
 *   api-version: string,
 *   signature?: string,
 *   requirements?: array{
 *     php?: array<string, string>,
 *     tool?: array<string, string>,
 *     plugin?: array<string, string>,
 *     composer?: array<string, string>,
 *   }
 * }
 * @psalm-type TPluginCatalog = array<string, list<TPluginCatalogEntry>>
 *
 * @deprecated This is the legacy repository loader.
 */
class PluginProviderRepository implements PluginVersionProvidingRepositoryInterface
{
    private string $sourceDir;

    /**
     * @psalm-suppress PropertyNotSetInConstructor
     * @psalm-var TPluginCatalog
     */
    private array $catalog;

    /**
     * Create a new instance.
     *
     * @param string $sourceDir
     */
    public function __construct(string $sourceDir)
    {
        $this->sourceDir = $sourceDir;
    }

    public function isFresh(): bool
    {
        return isset($this->catalog);
    }

    public function refresh(): void
    {
        /** @psalm-var TPluginCatalog|null $decoded */
        $decoded = json_decode(file_get_contents($this->sourceDir . '/catalog.json'), true);
        if (null === $decoded) {
            throw new RuntimeException('Failed to decode ' . $this->sourceDir . '/catalog.json');
        }

        $this->catalog = $decoded;
    }

    public function getPluginIterator(): Generator
    {
        if (!$this->isFresh()) {
            $this->refresh();
        }
        foreach ($this->catalog as $pluginName => $versions) {
            foreach ($versions as $version) {
                $absolutePathPlugin = StringUtil::makeAbsolutePath($version['file'], $this->sourceDir);
                $absolutePathSig = null;
                if (null !== ($signatureFile = $version['signature'] ?? null)) {
                    $absolutePathSig = StringUtil::makeAbsolutePath($signatureFile, $this->sourceDir);
                }
                yield new PhpFilePluginVersion(
                    $pluginName,
                    $version['plugin-version'],
                    '1.0.0',
                    $this->loadPluginRequirements($version['requirements'] ?? null),
                    $absolutePathPlugin,
                    $absolutePathSig,
                    PluginHash::createForFile($absolutePathPlugin)
                );
            }
        }
    }

    /** @psalm-param array{
     *   php?: array<string, string>,
     *   tool?: array<string, string>,
     *   plugin?: array<string, string>,
     *   composer?: array<string, string>,
     * }|null $requirements */
    private function loadPluginRequirements(?array $requirements): PluginRequirements
    {
        $result = new PluginRequirements();
        if (empty($requirements)) {
            return $result;
        }

        foreach (
            [
                'php'      => $result->getPhpRequirements(),
                'tool'     => $result->getToolRequirements(),
                'plugin'   => $result->getPluginRequirements(),
                'composer' => $result->getComposerRequirements(),
            ] as $key => $list
        ) {
            foreach ($requirements[$key] ?? [] as $name => $version) {
                $list->add(new VersionRequirement($name, $version));
            }
        }

        return $result;
    }
}
