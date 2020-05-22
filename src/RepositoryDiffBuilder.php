<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder;

use Phpcq\RepositoryBuilder\DiffBuilder\Diff;
use Phpcq\RepositoryBuilder\Repository\BootstrapInterface;
use Phpcq\RepositoryBuilder\Repository\InlineBootstrap;
use Phpcq\RepositoryBuilder\Repository\Tool;
use Phpcq\RepositoryBuilder\Repository\ToolHash;
use Phpcq\RepositoryBuilder\Repository\ToolVersion;
use Phpcq\RepositoryBuilder\Repository\VersionRequirement;

final class RepositoryDiffBuilder
{
    private string $baseDir;

    private array $oldData;

    public function __construct(string $baseDir)
    {
        $this->baseDir = $baseDir;
        $this->oldData = $this->loadRepository();
    }

    public function generate(): ?Diff
    {
        $newData = $this->loadRepository();

        return Diff::diff($this->oldData, $newData);
    }

    private function loadRepository(): array
    {
        if (!is_file($this->baseDir . '/repository.json')) {
            return [];
        }

        $data = [];
        $this->readFile($this->baseDir . '/repository.json', $data);

        return $data;
    }

    private function readFile(string $fileName, array &$data): void
    {
        $contents = json_decode(file_get_contents($fileName), true);
        foreach ($contents['phars'] as $toolName => $toolContent) {
            if (isset($toolContent['url'])) {
                // Include file
                $this->walkIncludeFile($toolContent['url'], dirname($fileName), $data);
                continue;
            }
            // Walk versions.
            $this->walkVersions($toolName, $toolContent, $contents['bootstraps'], dirname($fileName), $data);
        }
    }

    private function walkIncludeFile(string $relativePath, string $baseDir, array &$data): void
    {
        $this->readFile($baseDir . '/' . $relativePath, $data);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    private function walkVersions(
        string $toolName,
        array $versions,
        array $bootstraps,
        string $dirname,
        array &$data
    ): void {
        if (!isset($data[$toolName])) {
            $data[$toolName] = new Tool($toolName);
        }
        $bootstrapHash = [];
        foreach ($versions as $toolVersion) {
            // Load each bootstrap only once.
            $bootstrap = $toolVersion['bootstrap'];
            if (!isset($bootstrapHash[$bootstrap])) {
                $bootstrapHash[$bootstrap] = $this->loadBootstrap($bootstraps, $bootstrap, $this->baseDir);
            }

            $data[$toolName]->addVersion(new ToolVersion(
                $toolName,
                $toolVersion['version'],
                $toolVersion['phar-url'],
                $this->loadRequirements($toolVersion['requirements']),
                $this->loadHash($toolVersion['hash']),
                $toolVersion['signature'],
                $bootstrapHash[$bootstrap],
            ));
        }
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    private function loadBootstrap(array $bootstraps, string $bootstrap, string $baseDir): BootstrapInterface
    {
        $bootstrapInfo = $bootstraps[$bootstrap];
        switch ($bootstrapInfo['type']) {
            case 'inline':
                return new InlineBootstrap($bootstrapInfo['plugin-version'], $bootstrapInfo['code']);
            default:
        }
        // FIXME: add support for file based bootstrap loading when we dump it.
        throw new \RuntimeException('Unexpected bootstrap type encountered ' . $bootstrapInfo['type']);
    }

    private function loadHash(?array $hash): ?ToolHash
    {
        if (null === $hash) {
            return null;
        }

        return new ToolHash($hash['type'], $hash['value']);
    }

    /**
     * @param array|null $requirements
     *
     * @return VersionRequirement[]|null
     */
    private function loadRequirements(?array $requirements): ?array
    {
        if (empty($requirements)) {
            return null;
        }

        $result = [];
        foreach ($requirements as $name => $version) {
            $result[] = new VersionRequirement($name, $version);
        }

        return $result;
    }
}
