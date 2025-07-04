<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder;

use Composer\Semver\Comparator;
use Phpcq\RepositoryDefinition\AbstractHash;
use Phpcq\RepositoryDefinition\Plugin\PhpFilePluginVersion;
use Phpcq\RepositoryDefinition\Plugin\PluginInterface;
use Phpcq\RepositoryDefinition\Plugin\PluginRequirements;
use Phpcq\RepositoryDefinition\Tool\ToolInterface;
use Phpcq\RepositoryDefinition\Tool\ToolRequirements;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;
use Phpcq\RepositoryDefinition\VersionRequirementList;
use stdClass;
use Symfony\Component\Filesystem\Filesystem;

use function array_flip;
use function array_key_exists;
use function array_values;
use function glob;
use function hash_file;
use function is_array;
use function is_dir;
use function is_string;
use function json_encode;
use function sprintf;
use function usort;

use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * Dumps a repository as json.
 */
class JsonRepositoryWriter
{
    private const int JSON_FLAGS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

    private string $baseDir;

    private Filesystem $filesystem;

    /**
     * @var ToolInterface[]
     */
    private array $tools = [];

    /**
     * @var PluginInterface[]
     */
    private array $plugins = [];

    /** @var list<string> */
    private array $dumpedFileNames = [];

    /**
     * Create a new instance.
     *
     * @param string $baseDir
     */
    public function __construct(string $baseDir)
    {
        $this->baseDir    = $baseDir;
        $this->filesystem = new Filesystem();
    }

    /**
     * Add the passed tool to the json.
     *
     * @param ToolInterface $tool
     *
     * @return void
     */
    public function writeTool(ToolInterface $tool): void
    {
        $this->tools[] = $tool;
    }

    /**
     * Add the passed plugin to the json.
     *
     * @param PluginInterface $plugin
     *
     * @return void
     */
    public function writePlugin(PluginInterface $plugin): void
    {
        $this->plugins[] = $plugin;
    }

    /**
     * Save the repository.
     *
     * @return void
     */
    public function save(): void
    {
        $this->dumpedFileNames = [];
        $data = [
            'includes' => [],
        ];

        foreach ($this->tools as $tool) {
            if (null === $content = $this->processTool($tool)) {
                continue;
            }
            $data['includes'][] = $content;
        }
        unset($tool, $content);

        foreach ($this->plugins as $plugin) {
            if (null === $content = $this->processPlugin($plugin)) {
                continue;
            }
            $data['includes'][] = $content;
        }
        unset($plugin, $content);

        $this->dumpFile('repository.json', $data);
        unset($data);

        $flipped = array_flip($this->dumpedFileNames);
        $globbed = glob($this->baseDir . '/*');
        if (!is_array($globbed)) {
            return;
        }
        foreach ($globbed as $filename) {
            if (!is_dir($filename) && !array_key_exists($filename, $flipped)) {
                $this->filesystem->remove($filename);
            }
        }
    }

    private function processTool(ToolInterface $tool): ?array
    {
        $fileName         = sprintf('tool/%1$s/%1$s.json', $tool->getName());
        $fileNameAbsolute = $this->baseDir . '/' . $fileName;
        if ($tool->isEmpty()) {
            return null;
        }
        $data = [
            'tools' => [],
        ];

        foreach ($this->getToolVersions($tool) as $version) {
            if (!isset($data['tools'][$name = $tool->getName()])) {
                $data['tools'][$name] = [];
            }
            // no phar url, nothing to download.
            if (null === $pharUrl = $version->getPharUrl()) {
                continue;
            }

            $serialized = [
                'version'      => $version->getVersion(),
                'url'          => $pharUrl,
                'requirements' => $this->encodeToolRequirements($version->getRequirements()),
            ];
            if (null !== $hash = $this->encodeHash($version->getHash())) {
                $serialized['checksum'] = $hash;
            }
            if (null !== $signature = $version->getSignatureUrl()) {
                $serialized['signature'] = $signature;
            }
            $data['tools'][$name][] = $serialized;
        }
        if (!(bool) $data['tools']) {
            return null;
        }

        $this->dumpFile($fileName, $data);

        return [
            'url' => $fileName,
            'checksum' => [
                'type'  => 'sha-512',
                'value' => hash_file('sha512', $fileNameAbsolute),
            ],
        ];
    }

    private function encodeToolRequirements(ToolRequirements $requirements): stdClass
    {
        $output = new stdClass();
        foreach (
            [
                'php'      => $requirements->getPhpRequirements(),
                'composer' => $requirements->getComposerRequirements(),
            ] as $key => $list
        ) {
            if ([] !== $encoded = $this->encodeRequirements($list)) {
                $output->{$key} = $encoded;
            }
        }

        return $output;
    }

    private function processPlugin(PluginInterface $plugin): ?array
    {
        $fileName         = sprintf('plugin/%1$s/%1$s.json', $plugin->getName());
        $fileNameAbsolute = $this->baseDir . '/' . $fileName;
        if ($plugin->isEmpty()) {
            return null;
        }
        $data = [];
        foreach ($plugin as $version) {
            // if file plugin, copy file - dump it otherwise.
            $pluginFile    = sprintf('%1$s-%2$s.php', $plugin->getName(), $version->getVersion());
            $signatureFile = $pluginFile . '.asc';
            if ($version instanceof PhpFilePluginVersion) {
                $this->copyFile($version->getFilePath(), sprintf('plugin/%1$s/%2$s', $plugin->getName(), $pluginFile));
            }

            $serialized = [
                'api-version'  => $version->getApiVersion(),
                'version'      => $version->getVersion(),
                'type'         => 'php-file',
                'url'          => $pluginFile,
                'requirements' => $this->encodePluginRequirements($version->getRequirements()),
                'checksum'     => $this->encodeHash($version->getHash()),
            ];

            if ($version instanceof PhpFilePluginVersion) {
                if (null !== $signature = $version->getSignaturePath()) {
                    $this->copyFile($signature, $signatureFile);
                }
            }

            $data[] = $serialized;
        }

        $this->dumpFile($fileName, ['plugins' => [$plugin->getName() => $data]]);

        return [
            'url' => $fileName,
            'checksum' => [
                'type'  => 'sha-512',
                'value' => hash_file('sha512', $fileNameAbsolute),
            ],
        ];
    }

    private function encodePluginRequirements(PluginRequirements $requirements): stdClass
    {
        $output = new stdClass();
        foreach (
            [
                'php'      => $requirements->getPhpRequirements(),
                'tool'     => $requirements->getToolRequirements(),
                'plugin'   => $requirements->getPluginRequirements(),
                'composer' => $requirements->getComposerRequirements(),
            ] as $key => $list
        ) {
            if ([] !== $encoded = $this->encodeRequirements($list)) {
                $output->{$key} = $encoded;
            }
        }

        return $output;
    }

    private function encodeRequirements(VersionRequirementList $requirementList): array
    {
        $requirements = [];
        foreach ($requirementList->getIterator() as $requirement) {
            $requirements[$requirement->getName()] = $requirement->getConstraint();
        }

        return $requirements;
    }

    /**
     * @return null|string[]
     *
     * @psalm-return array{type: string, value: string}|null
     */
    private function encodeHash(?AbstractHash $hash): ?array
    {
        if (null === $hash) {
            return null;
        }
        return [
            'type' => $hash->getType(),
            'value' => $hash->getValue(),
        ];
    }

    private function copyFile(string $fileNameAbsolute, string $targetFileName): void
    {
        $this->dumpedFileNames[] = $fullFileName = $this->baseDir . '/' . $targetFileName;
        $this->filesystem->copy($fileNameAbsolute, $fullFileName, true);
    }

    private function dumpFile(string $fileName, array $contents): void
    {
        $this->dumpedFileNames[] = $fullFileName = $this->baseDir . '/' . $fileName;
        $encoded = json_encode($contents, self::JSON_FLAGS);
        assert(is_string($encoded));
        $this->filesystem->dumpFile($fullFileName, $encoded);
    }

    /** @return list<ToolVersionInterface> */
    private function getToolVersions(ToolInterface $tool): array
    {
        /** @var array<string, ToolVersionInterface> $versions */
        $versions = [];
        foreach ($tool as $version) {
            $versions[] = $version;
        }
        usort(
            $versions,
            static fn(ToolVersionInterface $first, ToolVersionInterface $second): int
                => Comparator::greaterThanOrEqualTo($first->getVersion(), $second->getVersion()) ? 1 : -1
        );

        return $versions;
    }
}
