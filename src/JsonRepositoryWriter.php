<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder;

use Phpcq\RepositoryBuilder\Repository\BootstrapHash;
use Phpcq\RepositoryBuilder\Repository\Tool;
use Phpcq\RepositoryBuilder\Repository\ToolHash;
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
    private array $tool = [];

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
    public function write(Tool $tool): void
    {
        $this->tool[] = $tool;
    }

    /**
     * Save the repository.
     *
     * @return void
     */
    public function save(): void
    {
        $data = [
            'bootstraps' => [],
            'phars' => [],
        ];

        foreach ($this->tool as $tool) {
            if (null === $content = $this->processTool($tool)) {
                continue;
            }
            $data['phars'][$tool->getName()] = $content;
        }

        $this->filesystem->dumpFile(
            $this->baseDir . '/repository.json',
            json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }

    private function processTool(Tool $tool): ?array
    {
        $fileName         = $tool->getName() . '.json';
        $fileNameAbsolute = $this->baseDir . '/' . $fileName;
        if ($tool->isEmpty()) {
            $this->filesystem->remove($fileNameAbsolute);
            return null;
        }
        $bootstraps = [];
        $data       = [
            'bootstraps' => [],
            'phars' => [],
        ];
        foreach ($tool->getIterator() as $version) {
            $bootstrap = $version->getBootstrap();
            if (null === $bootstrap) {
                // FIXME: Trigger error? We need a bootstrapper.
                continue;
            }

            if (!isset($bootstraps[$bootstrapHash = spl_object_hash($bootstrap)])) {
                $bootstraps[$bootstrapHash] = [
                    'name' => 'bootstrap-' . count($bootstraps),
                    'instance' => $bootstrap
                ];
            }
            $bootstrapName = $bootstraps[$bootstrapHash]['name'];
            if (!isset($data['phars'][$name = $tool->getName()])) {
                $data['phars'][$name] = [];
            }
            // no phar url, nothing to download.
            if (null === $pharUrl = $version->getPharUrl()) {
                continue;
            }

            $data['phars'][$name][] = [
                'version'      => $version->getVersion(),
                'phar-url'     => $pharUrl,
                'bootstrap'    => $bootstrapName,
                'requirements' => $this->encodeRequirements($version->getRequirements()),
                'hash'         => $this->encodeToolHash($version->getHash()),
                'signature'    => $version->getSignatureUrl(),
            ];
        }
        if (empty($data['phars'])) {
            return null;
        }
        foreach ($bootstraps as $bootstrap) {
            $data['bootstraps'][$bootstrap['name']] = [
                'plugin-version' => $bootstrap['instance']->getPluginVersion(),
                'type'           => 'inline',
                'code'           => $bootstrap['instance']->getCode(),
                'hash'           => $this->encodeBootstrapHash($bootstrap['instance']->getHash())
            ];
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

    private function encodeRequirements(VersionRequirementList $requirementList): stdClass
    {
        $requirements = new stdClass();
        foreach ($requirementList->getIterator() as $requirement) {
            $requirements->{$requirement->getName()} = $requirement->getConstraint();
        }
        return $requirements;
    }

    /**
     * @return null|string[]
     *
     * @psalm-return array{type: string, value: string}|null
     */
    private function encodeToolHash(?ToolHash $hash): ?array
    {
        if (null === $hash) {
            return null;
        }
        return [
            'type' => $hash->getType(),
            'value' => $hash->getValue(),
        ];
    }

    /**
     * @return null|string[]
     *
     * @psalm-return array{type: string, value: string}|null
     */
    private function encodeBootstrapHash(?BootstrapHash $hash): ?array
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
