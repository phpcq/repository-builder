<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\SourceProvider;

use Phpcq\RepositoryBuilder\SourceProvider\PluginProviderRepository;
use Phpcq\RepositoryDefinition\Plugin\PhpFilePluginVersion;
use PHPUnit\Framework\TestCase;

/** @covers \Phpcq\RepositoryBuilder\SourceProvider\PluginProviderRepository */
class PluginProviderRepositoryTest extends TestCase
{
    public function testProvidesVersions(): void
    {
        $provider = new PluginProviderRepository($baseDir = realpath(__DIR__ . '/../fixtures/plugins'));

        $this->assertEquals(
            [
                new PhpFilePluginVersion(
                    'plugin-a',
                    '1.0.0',
                    '1.0.0',
                    null,
                    $baseDir . '/plugin-a.php',
                    null
                ),
                new PhpFilePluginVersion(
                    'plugin-b',
                    '1.0.0',
                    '1.0.0',
                    null,
                    $baseDir . '/plugin-b1.php',
                    null
                ),
                new PhpFilePluginVersion(
                    'plugin-b',
                    '1.0.0',
                    '1.0.0',
                    null,
                    $baseDir . '/plugin-b2.php',
                    null
                ),
            ],
            iterator_to_array($provider->getIterator())
        );
    }
}
