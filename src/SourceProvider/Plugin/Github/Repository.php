<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\SourceProvider\Plugin\Github;

use Generator;
use InvalidArgumentException;
use Phpcq\RepositoryBuilder\Exception\DataNotAvailableException;
use Phpcq\RepositoryBuilder\SourceProvider\PluginVersionProvidingRepositoryInterface;
use Phpcq\RepositoryDefinition\Plugin\PhpFilePluginVersion;
use Phpcq\RepositoryDefinition\Plugin\PluginHash;
use Phpcq\RepositoryDefinition\Plugin\PluginRequirements;
use Phpcq\RepositoryDefinition\VersionRequirement;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @psalm-import-type TRequirementsSchema from JsonEntry
 * @psalm-import-type TToolRequirementSchema from JsonEntry
 * @psalm-import-type TPluginRequirementSchema from JsonEntry
 */
class Repository implements PluginVersionProvidingRepositoryInterface
{
    private HttpClientInterface $httpClient;

    /** @var array<string, list<PhpFilePluginVersion>> */
    private array $catalog = [];

    private JsonLoader $jsonLoader;

    public function __construct(JsonLoader $jsonLoader, HttpClientInterface $httpClient)
    {
        $this->jsonLoader = $jsonLoader;
        $this->httpClient = $httpClient;
    }

    public function isFresh(): bool
    {
        return !empty($this->catalog);
    }

    public function refresh(): void
    {
        $this->catalog = [];
        foreach ($this->jsonLoader->getJsonFileIterator() as $jsonFile) {
            $this->handleJsonFile($jsonFile);
        }
    }

    public function getPluginIterator(): Generator
    {
        if (!$this->isFresh()) {
            $this->refresh();
        }
        foreach ($this->catalog as $versions) {
            foreach ($versions as $version) {
                yield $version;
            }
        }
    }

    private function handleJsonFile(JsonEntry $jsonEntry): void
    {
        try {
            $name = $jsonEntry->getName();

            // FIXME: need to handle plugin type "phar" here.
            if ('php-file' !== $jsonEntry->getType()) {
                throw new InvalidArgumentException('Unsupported plugin type encountered:' . $jsonEntry->getType());
            }

            $version = new PhpFilePluginVersion(
                $name,
                $jsonEntry->getVersion(),
                $jsonEntry->getApiVersion(),
                $this->loadPluginRequirements($jsonEntry->getRequirements()),
                $jsonEntry->getPluginUrl(true),
                $jsonEntry->getSignatureUrl(true),
                $this->createHash($jsonEntry->getPluginUrl(true))
            );

            if (!array_key_exists($name, $this->catalog)) {
                $this->catalog[$name] = [];
            }

            $this->catalog[$name][] = $version;
        } catch (DataNotAvailableException $exception) {
            // If no json file present, ignore this one.
            return;
        }
    }

    /** @param TRequirementsSchema|null $requirements */
    private function loadPluginRequirements(?array $requirements): PluginRequirements
    {
        $result = new PluginRequirements();
        if (empty($requirements)) {
            return $result;
        }

        foreach (
            [
                'php'      => $result->getPhpRequirements(),
                'composer' => $result->getComposerRequirements(),
            ] as $key => $list
        ) {
            /** @var string $constraints */
            foreach ($requirements[$key] ?? [] as $name => $constraints) {
                $list->add(new VersionRequirement($name, $constraints));
            }
        }

        foreach (
            [
                'tool'   => $result->getToolRequirements(),
                'plugin' => $result->getPluginRequirements(),
            ] as $key => $list
        ) {
            /** @var TToolRequirementSchema|TPluginRequirementSchema $requirement */
            foreach ($requirements[$key] ?? [] as $name => $requirement) {
                $list->add(new VersionRequirement($name, $requirement['constraints']));
            }
        }

        return $result;
    }

    private function createHash(string $absoluteUriPlugin): PluginHash
    {
        $scheme = parse_url($absoluteUriPlugin, PHP_URL_SCHEME);
        if (!empty($scheme) && $scheme !== 'file') {
            return PluginHash::createForString(
                $this->httpClient->request('GET', $absoluteUriPlugin)->getContent()
            );
        }

        return PluginHash::createForFile($absoluteUriPlugin);
    }
}
