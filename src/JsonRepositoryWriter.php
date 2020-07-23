<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder;

use Phpcq\RepositoryBuilder\Repository\AbstractHash;
use Phpcq\RepositoryBuilder\Repository\Plugin\PhpFilePluginVersion;
use Phpcq\RepositoryBuilder\Repository\Plugin\Plugin;
use Phpcq\RepositoryBuilder\Repository\Plugin\PluginRequirements;
use Phpcq\RepositoryBuilder\Repository\Tool\Tool;
use Phpcq\RepositoryBuilder\Repository\Tool\ToolRequirements;
use Phpcq\RepositoryBuilder\Repository\VersionRequirementList;
use stdClass;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Dumps a repository as json.
 */
class JsonRepositoryWriter
{
    private string $baseDir;

    private Filesystem $filesystem;

    /**
     * @var Tool[]
     */
    private array $tools = [];

    /**
     * @var Plugin[]
     */
    private array $plugins = [];

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
     * @param Tool $tool
     *
     * @return void
     */
    public function writeTool(Tool $tool): void
    {
        $this->tools[] = $tool;
    }

    /**
     * Add the passed plugin to the json.
     *
     * @param Plugin $plugin
     *
     * @return void
     */
    public function writePlugin(Plugin $plugin): void
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
        $data = [
            'includes' => [],
        ];

        foreach ($this->tools as $tool) {
            if (null === $content = $this->processTool($tool)) {
                continue;
            }
            $data['includes'][] = $content;
        }

        foreach ($this->plugins as $plugin) {
            if (null === $content = $this->processPlugin($plugin)) {
                continue;
            }
            $data['includes'][] = $content;
        }

        $this->filesystem->dumpFile(
            $this->baseDir . '/repository.json',
            json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }

    private function processTool(Tool $tool): ?array
    {
        $fileName         = $tool->getName() . '-tool.json';
        $fileNameAbsolute = $this->baseDir . '/' . $fileName;
        if ($tool->isEmpty()) {
            $this->filesystem->remove($fileNameAbsolute);
            return null;
        }
        $data = [
            'tools' => [],
        ];
        foreach ($tool->getIterator() as $version) {
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
        if (empty($data['tools'])) {
            return null;
        }

        $this->filesystem->dumpFile(
            $fileNameAbsolute,
            json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        return [
            'url' => './' . $fileName,
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

    private function processPlugin(Plugin $plugin): ?array
    {
        $fileName         = $plugin->getName() . '-plugin.json';
        $fileNameAbsolute = $this->baseDir . '/' . $fileName;
        if ($plugin->isEmpty()) {
            $this->filesystem->remove($fileNameAbsolute);
            return null;
        }
        $data = [];
        foreach ($plugin->getIterator() as $version) {
            // if file plugin, copy file - dump it otherwise.
            $pluginFile    = $plugin->getName() . '-' . $version->getVersion() . '.php';
            $signatureFile = $pluginFile . '.asc';
            if ($version instanceof PhpFilePluginVersion) {
                $this->filesystem->copy($version->getFilePath(), $this->baseDir . '/' . $pluginFile);
            } else {
                $this->filesystem->dumpFile($this->baseDir . '/' . $pluginFile, $version->getCode());
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
                    $this->filesystem->copy($signature, $this->baseDir . '/' . $signatureFile);
                } else {
                    $this->filesystem->remove($this->baseDir . '/' . $signatureFile);
                }
            } else {
                if (null !== $signature = $version->getSignature()) {
                    $serialized['signature'] = $signatureFile;
                    $this->filesystem->dumpFile($this->baseDir . '/' . $signatureFile, $signature);
                } else {
                    $this->filesystem->remove($this->baseDir . '/' . $signatureFile);
                }
            }

            $data[] = $serialized;
        }

        $this->filesystem->dumpFile(
            $fileNameAbsolute,
            json_encode(['plugins' => [$plugin->getName() => $data]], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        return [
            'url' => './' . $fileName,
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
}
