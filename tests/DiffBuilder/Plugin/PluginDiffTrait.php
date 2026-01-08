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

            $plugVersion = $this->mockPluginVersion($name, $version, $requirements);
            $plugin->addVersion($plugVersion);
        }

        return $plugin;
    }

    private function mockPluginVersion(
        string $name,
        string $version,
        ?PluginRequirements $requirements = null,
        ?PluginHash $hash = null,
        string $apiVersion = '1.0.0'
    ): PluginVersionInterface {
        $plugVersion = $this->getMockBuilder(PluginVersionInterface::class)->getMock();
        $plugVersion->method('getName')->willReturn($name);
        $plugVersion->method('getApiVersion')->willReturn($apiVersion);
        $plugVersion->method('getVersion')->willReturn($version);
        $plugVersion->expects($this->never())->method('getFilePath');
        $plugVersion->expects($this->never())->method('getSignaturePath');
        $plugVersion->method('getHash')->willReturn($hash ?? PluginHash::create(PluginHash::SHA_512, 'abcdef...'));
        $plugVersion->method('getRequirements')->willReturn($requirements ?? new PluginRequirements());
        $plugVersion->expects($this->never())->method('merge');

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
        $plugVersion = $this->getMockBuilder(PhpFilePluginVersionInterface::class)->getMock();
        $plugVersion->method('getName')->willReturn($name);
        $plugVersion->method('getApiVersion')->willReturn($apiVersion);
        $plugVersion->method('getVersion')->willReturn($version);
        $plugVersion->method('getFilePath')->willReturn($filePath);
        $plugVersion->method('getSignaturePath')->willReturn($signaturePath);
        $plugVersion->method('getHash')->willReturn($hash ?? PluginHash::create(PluginHash::SHA_512, 'abcdef...'));
        $plugVersion->method('getRequirements')->willReturn($requirements ?? new PluginRequirements());
        $plugVersion->expects($this->never())->method('merge');

        return $plugVersion;
    }
}
