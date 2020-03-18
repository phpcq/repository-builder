<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder;

use Phpcq\RepositoryBuilder\Repository\Tool;
use Phpcq\RepositoryBuilder\Repository\ToolHash;
use Phpcq\RepositoryBuilder\Repository\VersionRequirementList;
use stdClass;

/**
 * Dumps a repository as json.
 */
class JsonRepositoryWriter
{
    private string $baseDir;

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
        $this->baseDir = $baseDir;
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

        $bootstraps = [];
        foreach ($this->tool as $tool) {
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
                    'hash'         => $this->encodeHash($version->getHash()),
                    'signature'    => $version->getSignatureUrl(),
                ];
            }
        }
        foreach ($bootstraps as $hash => $bootstrap) {
            $data['bootstraps'][$bootstrap['name']] = [
                'plugin-version' => $bootstrap['instance']->getPluginVersion(),
                'type'           => 'inline',
                'code'           => $bootstrap['instance']->getCode(),
            ];
        }

        file_put_contents(
            $this->baseDir . '/repository.json',
            json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }

    private function encodeRequirements(VersionRequirementList $requirementList): stdClass
    {
        $requirements = new stdClass();
        foreach ($requirementList->getIterator() as $requirement) {
            $requirements->{$requirement->getName()} = $requirement->getConstraint();
        }
        return $requirements;
    }

    private function encodeHash(?ToolHash $hash)
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