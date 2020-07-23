<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Repository;

use Phpcq\RepositoryBuilder\Repository\Plugin\PhpFilePluginVersion;
use Phpcq\RepositoryBuilder\Repository\Plugin\PhpInlinePluginVersion;
use Phpcq\RepositoryBuilder\Repository\Plugin\Plugin;
use Phpcq\RepositoryBuilder\Repository\Plugin\PluginHash;
use Phpcq\RepositoryBuilder\Repository\Plugin\PluginRequirements;
use Phpcq\RepositoryBuilder\Repository\Plugin\PluginVersionInterface;
use Phpcq\RepositoryBuilder\Repository\Tool\Tool;
use Phpcq\RepositoryBuilder\Repository\Tool\ToolHash;
use Phpcq\RepositoryBuilder\Repository\Tool\ToolRequirements;
use Phpcq\RepositoryBuilder\Repository\Tool\ToolVersion;
use RuntimeException;

/**
 * @psalm-type TRepositoryCheckSum = array{
 *   type: string,
 *   value: string,
 * }
 * @psalm-type TRepositoryIncludeList = list<array{
 *   url: string,
 *   checksum: TRepositoryCheckSum
 * }>
 * @psalm-type TRepositoryToolRequirements = array{
 *   php?: array<string, string>,
 *   composer?: array<string, string>,
 * }
 * @psalm-type TRepositoryToolVersion = array{
 *   version: string,
 *   url: string,
 *   requirements: TRepositoryToolRequirements,
 *   checksum?: TRepositoryCheckSum,
 *   signature?: string,
 * }
 * @psalm-type TRepositoryPluginRequirements = array{
 *   php?: array<string, string>,
 *   tool?: array<string, string>,
 *   plugin?: array<string, string>,
 *   composer?: array<string, string>,
 * }
 * @psalm-type TRepositoryPluginVersion = array{
 *   type: 'php-file'|'php-inline',
 *   version: string,
 *   api-version: string,
 *   requirements?: TRepositoryPluginRequirements,
 *   url?: string,
 *   code?: string,
 *   checksum?: TRepositoryCheckSum,
 *   signature?: string,
 * }
 * @psalm-type TRepositoryInclude = array{
 *  url: string,
 *  checksum: TRepositoryCheckSum
 * }
 * @psalm-type TRepositoryTool = list<TRepositoryToolVersion>
 * @psalm-type TRepositoryPlugin = list<TRepositoryPluginVersion>
 * @psalm-type TRepositoryContents = array{
 *  includes?: list<TRepositoryInclude>,
 *  tools?: array<string, TRepositoryTool>,
 *  plugins?: array<string, TRepositoryPlugin>,
 * }
 */
final class RepositoryLoader
{
    /** @psalm-var  array<string, Tool> */
    private array $tools = [];

    /** @psalm-var  array<string, Plugin> */
    private array $plugins = [];

    /** @psalm-return array{tools: list<Tool>, plugins: list<Plugin>}|null */
    public static function load(string $baseDir): ?array
    {
        if (!is_file($baseDir . '/repository.json')) {
            return null;
        }

        $instance = new RepositoryLoader();
        $instance->readFile($baseDir . '/repository.json');

        /** @psalm-var list<Tool> $tools */
        $tools = array_values($instance->tools);
        /** @psalm-var list<Plugin> $plugins */
        $plugins = array_values($instance->plugins);

        if (empty($tools) && empty($plugins)) {
            return null;
        }

        return [
            'tools'   => $tools,
            'plugins' => $plugins,
        ];
    }

    private function __construct()
    {
    }

    private function readFile(string $fileName): void
    {
        /** @psalm-var TRepositoryContents $contents */
        $contents = json_decode(file_get_contents($fileName), true);
        $baseDir  = dirname($fileName);
        if (isset($contents['tools'])) {
            $this->walkTools($contents['tools']);
        }
        if (isset($contents['plugins'])) {
            $this->walkPlugins($contents['plugins'], $baseDir);
        }
        if (isset($contents['includes'])) {
            $this->walkIncludeFiles($contents['includes'], $baseDir);
        }
    }

    /**
     * @psalm-param array<string, TRepositoryTool> $tools
     */
    private function walkTools(array $tools): void
    {
        foreach ($tools as $toolName => $versions) {
            $this->walkToolVersions($toolName, $versions);
        }
    }

    /**
     * @psalm-param list<TRepositoryToolVersion> $versions
     */
    private function walkToolVersions(string $toolName, array $versions): void
    {
        if (!isset($this->tools[$toolName])) {
            $this->tools[$toolName] = new Tool($toolName);
        }
        foreach ($versions as $toolVersion) {
            $this->tools[$toolName]->addVersion(new ToolVersion(
                $toolName,
                $toolVersion['version'],
                $toolVersion['url'],
                $this->loadToolRequirements($toolVersion['requirements']),
                $this->loadToolHash($toolVersion['checksum'] ?? null),
                $toolVersion['signature'] ?? null,
            ));
        }
    }

    /**
     * @psalm-param TRepositoryCheckSum|null $hash
     */
    private function loadToolHash(?array $hash): ?ToolHash
    {
        if (null === $hash) {
            return null;
        }

        return ToolHash::create($hash['type'], $hash['value']);
    }

    /**
     * @psalm-param array<string, TRepositoryPlugin> $plugins
     */
    private function walkPlugins(array $plugins, string $baseDir): void
    {
        foreach ($plugins as $pluginName => $versions) {
            $this->walkPluginVersions($pluginName, $versions, $baseDir);
        }
    }

    /**
     * @psalm-param list<TRepositoryPluginVersion> $versions
     */
    private function walkPluginVersions(string $pluginName, array $versions, string $baseDir): void
    {
        if (!isset($this->plugins[$pluginName])) {
            $this->plugins[$pluginName] = new Plugin($pluginName);
        }
        foreach ($versions as $pluginVersion) {
            $this->plugins[$pluginName]->addVersion(
                $this->loadPluginVersion($pluginVersion, $pluginName, $baseDir)
            );
        }
    }

    /**
     * @psalm-param TRepositoryIncludeList $includes
     */
    private function walkIncludeFiles(array $includes, string $baseDir): void
    {
        foreach ($includes as $include) {
            $this->readFile($baseDir . '/' . $include['url']);
        }
    }

    /** @psalm-param TRepositoryPluginVersion $information */
    private function loadPluginVersion(array $information, string $name, string $baseDir): PluginVersionInterface
    {
        switch ($information['type']) {
            case 'php-file':
                assert(isset($information['url']), 'Code is mandatory for inline plugins');
                return new PhpFilePluginVersion(
                    $name,
                    $information['version'],
                    $information['api-version'],
                    $this->loadPluginRequirements($information['requirements'] ?? []),
                    $baseDir . '/' . $information['url'],
                    $information['signature'] ?? null,
                    $this->loadPluginHash($information['checksum'] ?? null)
                );
            case 'php-inline':
                assert(isset($information['code']), 'Code is mandatory for inline plugins');
                return new PhpInlinePluginVersion(
                    $name,
                    $information['version'],
                    $information['api-version'],
                    $this->loadPluginRequirements($information['requirements'] ?? []),
                    $information['code']
                );
        }

        throw new RuntimeException('Unexpected plugin type encountered ' . $information['type']);
    }

    /**
     * @psalm-param TRepositoryCheckSum|null $hash
     */
    private function loadPluginHash(?array $hash): ?PluginHash
    {
        if (null === $hash) {
            return null;
        }

        return PluginHash::create($hash['type'], $hash['value']);
    }

    /** @psalm-param TRepositoryToolRequirements|null $requirements */
    private function loadToolRequirements(?array $requirements): ToolRequirements
    {
        $result = new ToolRequirements();
        if (empty($requirements)) {
            return $result;
        }

        foreach (
            [
                'php'      => $result->getPhpRequirements(),
                'composer' => $result->getComposerRequirements(),
            ] as $key => $list
        ) {
            foreach ($requirements[$key] ?? [] as $name => $version) {
                $list->add(new VersionRequirement($name, $version));
            }
        }

        return $result;
    }

    /** @psalm-param TRepositoryPluginRequirements|null $requirements */
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
