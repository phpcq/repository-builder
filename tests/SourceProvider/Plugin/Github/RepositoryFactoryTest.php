<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\SourceProvider\Plugin\Github;

use Phpcq\RepositoryBuilder\Api\GithubClient;
use Phpcq\RepositoryBuilder\Exception\DataNotAvailableException;
use Phpcq\RepositoryBuilder\SourceProvider\CompoundRepository;
use Phpcq\RepositoryBuilder\SourceProvider\LoaderContext;
use Phpcq\RepositoryBuilder\SourceProvider\Plugin\Github\Repository;
use Phpcq\RepositoryBuilder\SourceProvider\Plugin\Github\RepositoryFactory;
use Phpcq\RepositoryBuilder\SourceProvider\PluginVersionProvidingRepositoryInterface;
use Phpcq\RepositoryBuilder\SourceProvider\RepositoryLoader;
use Phpcq\RepositoryBuilder\SourceProvider\SourceRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionProperty;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/** @covers \Phpcq\RepositoryBuilder\SourceProvider\Plugin\Github\RepositoryFactory */
class RepositoryFactoryTest extends TestCase
{
    public function testFunctionality(): void
    {
        $client = $this->getMockBuilder(HttpClientInterface::class)->disableOriginalConstructor()->getMock();
        $github = $this->getMockBuilder(GithubClient::class)->disableOriginalConstructor()->getMock();
        new RepositoryFactory($client, $github);
        // We could instantiate the factory.
        $this->addToAssertionCount(1);
    }

    public function invalidConfigurationProvider(): array
    {
        return [
            'integer is invalid' => [[
                'repositories' => 1
            ]],
            'string is invalid' => [[
                'repositories' => 'invalid'
            ]],
        ];
    }

    /** @dataProvider invalidConfigurationProvider */
    public function testThrowsWithInvalidRepositoryConfiguration(array $config): void
    {
        $client    = $this->getMockBuilder(HttpClientInterface::class)->disableOriginalConstructor()->getMock();
        $github    = $this->getMockBuilder(GithubClient::class)->disableOriginalConstructor()->getMock();
        $factory   = new RepositoryFactory($client, $github);
        $factories = $this->getMockForAbstractClass(ContainerInterface::class);
        $loader    = new RepositoryLoader($factories);
        $context   = LoaderContext::create($loader);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No source repositories configured');

        $factory->create($config, $context);
    }

    public function testLoadsSourceRepositories(): void
    {
        $client    = $this->getMockBuilder(HttpClientInterface::class)->disableOriginalConstructor()->getMock();
        $github    = $this->getMockBuilder(GithubClient::class)->disableOriginalConstructor()->getMock();
        $factory   = new RepositoryFactory($client, $github);
        $loader    = $this->getMockBuilder(RepositoryLoader::class)->disableOriginalConstructor()->getMock();
        $context   = LoaderContext::create($loader);
        $config    = ['repositories' => ['vendor1/plugin1']];
        $github
            ->expects(self::once())
            ->method('fetchTags')
            ->with('vendor1/plugin1')
            ->willReturn([['ref' => 'refs/tags/1.0.0']]);
        $github
            ->expects(self::once())
            ->method('fetchFile')
            ->with('vendor1/plugin1', '1.0.0', 'phpcq-plugin.json')
            ->willReturn([
                'name'         => 'vendor1-plugin1',
                'type'         => 'php-file',
                'url'          => 'https://example.org/repository-name/tag-name/src/file.php',
                'api-version'  => '1.0.0',
                'requirements' => [
                    'tool' => [
                        'tool1' => [
                            'sources' => [
                                [
                                    'type' => 'dummy',
                                    'tool' => 'tool1',
                                ]
                            ]
                        ]
                    ],
                    'plugin' => [
                        'plugin1' => [
                            'sources' => [
                                [
                                    'type' => 'dummy',
                                    'tool' => 'plugin1',
                                ]
                            ]
                        ]
                    ]
                ],
            ]);

        $mock1 = $this->getMockForAbstractClass(PluginVersionProvidingRepositoryInterface::class);
        $mock2 = $this->getMockForAbstractClass(PluginVersionProvidingRepositoryInterface::class);
        $loader
            ->expects(self::exactly(2))
            ->method('load')
            ->willReturnCallback(
                function (array $config, LoaderContext $context) use ($mock1, $mock2): SourceRepositoryInterface {
                    switch ($config) {
                        case ['type' => 'dummy', 'tool' => 'plugin1']:
                            return $mock1;
                        case ['type' => 'dummy', 'tool' => 'tool1']:
                            return $mock2;
                    }
                    self::fail('Invalid configuration');
                }
            );

        $result = $factory->create($config, $context);

        self::assertInstanceOf(CompoundRepository::class, $result);
        $repos = $this->extractPluginProviders($result);
        self::assertCount(3, $repos);
        self::assertInstanceOf(Repository::class, $repos[0]);
        self::assertSame($mock1, $repos[1]);
        self::assertSame($mock2, $repos[2]);
    }

    public function testIgnoresTagsWithoutJson(): void
    {
        $client    = $this->getMockBuilder(HttpClientInterface::class)->disableOriginalConstructor()->getMock();
        $github    = $this->getMockBuilder(GithubClient::class)->disableOriginalConstructor()->getMock();
        $factory   = new RepositoryFactory($client, $github);
        $loader    = $this->getMockBuilder(RepositoryLoader::class)->disableOriginalConstructor()->getMock();
        $context   = LoaderContext::create($loader);
        $config    = ['repositories' => ['vendor1/plugin1']];
        $github
            ->expects(self::once())
            ->method('fetchTags')
            ->with('vendor1/plugin1')
            ->willReturn([['ref' => 'refs/tags/1.0.0']]);
        $github
            ->expects(self::once())
            ->method('fetchFile')
            ->with('vendor1/plugin1', '1.0.0', 'phpcq-plugin.json')
            ->willThrowException(new DataNotAvailableException());

        $loader->expects(self::never())->method('load');

        $result = $factory->create($config, $context);

        self::assertInstanceOf(CompoundRepository::class, $result);
        $repos = $this->extractPluginProviders($result);
        self::assertCount(1, $repos);
        self::assertInstanceOf(Repository::class, $repos[0]);
    }

    /** @return list<PluginVersionProvidingRepositoryInterface> */
    private function extractPluginProviders(CompoundRepository $repository): array
    {
        // Yeah, I know, reflection is bad but hey...
        $reflection = new ReflectionProperty(CompoundRepository::class, 'pluginProviders');
        $reflection->setAccessible(true);

        return $reflection->getValue($repository);
    }
}
