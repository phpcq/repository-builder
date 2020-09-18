<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\DiffBuilder\Plugin;

use Phpcq\RepositoryDefinition\Plugin\PhpFilePluginVersionInterface;
use Phpcq\RepositoryDefinition\Plugin\Plugin;
use Phpcq\RepositoryDefinition\Plugin\PluginHash;
use Phpcq\RepositoryDefinition\Plugin\PluginRequirements;
use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\RepositoryDefinition\VersionRequirement;

trait PluginDiffTrait
{
    private function mockPluginWithVersions(string $name, array $versions): Plugin
    {
        $plugin = new Plugin($name);
        foreach ($versions as $version) {
            $requirements = new PluginRequirements();
            $requirements->getPhpRequirements()->add(new VersionRequirement('php', '^7.4'));

            $plugVersion = $this->mockPluginVersion($name, $version, 'code', $requirements);
            $plugin->addVersion($plugVersion);
        }

        return $plugin;
    }

    private function mockPluginVersion(
        string $name,
        string $version,
        string $code = 'code',
        ?PluginRequirements $requirements = null,
        ?PluginHash $hash = null,
        ?string $signature = 'signature',
        string $apiVersion = '1.0.0'
    ): PluginVersionInterface {
        $plugVersion = $this->getMockForAbstractClass(PluginVersionInterface::class);
        $plugVersion->method('getName')->willReturn($name);
        $plugVersion->method('getApiVersion')->willReturn($apiVersion);
        $plugVersion->method('getVersion')->willReturn($version);
        $plugVersion->method('getCode')->willReturn($code);
        $plugVersion->method('getSignature')->willReturn($signature);
        $plugVersion->method('getHash')->willReturn($hash ?? PluginHash::create(PluginHash::SHA_512, 'abcdef...'));
        $plugVersion->method('getRequirements')->willReturn($requirements ?? new PluginRequirements());

        return $plugVersion;
    }
    private function mockPhpFilePluginVersionInterface(
        string $name,
        string $version,
        string $filePath = '/path/to/file',
        ?PluginRequirements $requirements = null,
        ?PluginHash $hash = null,
        ?string $signaturePath = '/path/to/signature',
        string $apiVersion = '1.0.0'
    ): PhpFilePluginVersionInterface {
        $plugVersion = $this->getMockForAbstractClass(PhpFilePluginVersionInterface::class);
        $plugVersion->method('getName')->willReturn($name);
        $plugVersion->method('getApiVersion')->willReturn($apiVersion);
        $plugVersion->method('getVersion')->willReturn($version);
        $plugVersion->expects($this->never())->method('getCode');
        $plugVersion->expects($this->never())->method('getSignature');
        $plugVersion->method('getHash')->willReturn($hash ?? PluginHash::create(PluginHash::SHA_512, 'abcdef...'));
        $plugVersion->method('getRequirements')->willReturn($requirements ?? new PluginRequirements());
        $plugVersion->method('getFilePath')->willReturn($filePath);
        $plugVersion->method('getSignaturePath')->willReturn($signaturePath);

        return $plugVersion;
    }
}
