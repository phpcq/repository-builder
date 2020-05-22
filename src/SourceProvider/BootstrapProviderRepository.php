<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\SourceProvider;

use Composer\Semver\VersionParser;
use InvalidArgumentException;
use Phpcq\RepositoryBuilder\Repository\BootstrapInterface;
use Phpcq\RepositoryBuilder\Repository\FileBootstrap;
use Phpcq\RepositoryBuilder\Repository\ToolVersion;
use Phpcq\RepositoryBuilder\Util\StringUtil;

class BootstrapProviderRepository implements EnrichingRepositoryInterface
{
    private string $sourceDir;

    /**
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private array $catalog;

    private VersionParser $versionParser;

    /**
     * @var BootstrapInterface[]
     */
    private array $instances = [];

    /**
     * Create a new instance.
     *
     * @param string $sourceDir
     */
    public function __construct(string $sourceDir)
    {
        $this->sourceDir     = $sourceDir;
        $this->versionParser = new VersionParser();
        $this->refresh();
    }

    public function isFresh(): bool
    {
        return true;
    }

    public function refresh(): void
    {
        $this->catalog = json_decode(file_get_contents($this->sourceDir . '/catalog.json'), true);
    }

    public function supports(ToolVersion $version): bool
    {
        try {
            $this->findToolBootstrap($version);
            return true;
        } catch (InvalidArgumentException $exception) {
            return false;
        }
    }

    public function enrich(ToolVersion $version): void
    {
        // Do not override existing bootstrap.
        if (null !== $version->getBootstrap()) {
            return;
        }

        $bootstrap = $this->findToolBootstrap($version);

        $toolName = $version->getName() . '#' . $bootstrap['constraint'];

        if (!isset($this->instances[$toolName])) {
            $this->instances[$toolName] = new FileBootstrap(
                $bootstrap['plugin-version'],
                StringUtil::makeAbsolutePath($bootstrap['file'], $this->sourceDir)
            );
        }
        $version->setBootstrap($this->instances[$toolName]);
    }

    private function findToolBootstrap(ToolVersion $version): array
    {
        if (!isset($this->catalog[$version->getName()])) {
            throw new InvalidArgumentException('Tool unsupported: ' . $version->getName());
        }

        $toolVersion = $this->versionParser->parseConstraints($version->getVersion());
        foreach ($this->catalog[$version->getName()] as $bootstrap) {
            $constraint = $this->versionParser->parseConstraints($bootstrap['constraint']);
            if ($constraint->matches($toolVersion)) {
                return $bootstrap;
            }
        }

        throw new InvalidArgumentException('No bootstrap information found.');
    }
}
