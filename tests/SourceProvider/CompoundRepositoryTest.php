<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\SourceProvider;

use Closure;
use InvalidArgumentException;
use Phpcq\RepositoryBuilder\SourceProvider\CompoundRepository;
use Phpcq\RepositoryBuilder\SourceProvider\PluginVersionProvidingRepositoryInterface;
use Phpcq\RepositoryBuilder\SourceProvider\SourceRepositoryInterface;
use Phpcq\RepositoryBuilder\SourceProvider\Tool\ToolVersionEnrichingRepositoryInterface;
use Phpcq\RepositoryBuilder\SourceProvider\ToolVersionProvidingRepositoryInterface;
use Phpcq\RepositoryBuilder\Test\SourceProvider\MockRepositoryInterface\PluginVersionProvidingInterface;
use Phpcq\RepositoryBuilder\Test\SourceProvider\MockRepositoryInterface\ToolVersionEnrichingInterface;
use Phpcq\RepositoryBuilder\Test\SourceProvider\MockRepositoryInterface\ToolVersionProvidingInterface;
use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Phpcq\RepositoryBuilder\SourceProvider\CompoundRepository
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CompoundRepositoryTest extends TestCase
{
    public function testThrowsOnUnsupported(): void
    {
        $mock = $this->getMockForAbstractClass(SourceRepositoryInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown provider type ' . get_class($mock));

        new CompoundRepository($mock);
    }

    public function testAcceptsPluginProvider(): void
    {
        new CompoundRepository($this->getMockForAbstractClass(PluginVersionProvidingInterface::class));

        $this->addToAssertionCount(1);
    }

    public function testAcceptsToolProvider(): void
    {
        new CompoundRepository($this->getMockForAbstractClass(ToolVersionProvidingInterface::class));

        $this->addToAssertionCount(1);
    }

    public function testAcceptsEnrichingProvider(): void
    {
        new CompoundRepository($this->getMockForAbstractClass(ToolVersionEnrichingRepositoryInterface::class));

        $this->addToAssertionCount(1);
    }

    public function isFreshProvider(): array
    {
        $pluginTrue = $this->getMockForAbstractClass(PluginVersionProvidingInterface::class);
        $pluginTrue->method('isFresh')->willReturn(true);
        $pluginFalse = $this->getMockForAbstractClass(PluginVersionProvidingInterface::class);
        $pluginFalse->method('isFresh')->willReturn(false);

        $toolTrue = $this->getMockForAbstractClass(ToolVersionProvidingInterface::class);
        $toolTrue->method('isFresh')->willReturn(true);
        $toolFalse = $this->getMockForAbstractClass(ToolVersionProvidingInterface::class);
        $toolFalse->method('isFresh')->willReturn(false);

        $enrichingTrue = $this->getMockForAbstractClass(ToolVersionEnrichingRepositoryInterface::class);
        $enrichingTrue->method('isFresh')->willReturn(true);
        $enrichingFalse = $this->getMockForAbstractClass(ToolVersionEnrichingRepositoryInterface::class);
        $enrichingFalse->method('isFresh')->willReturn(false);

        return [
            'without children' => [
                'expected' => true,
                'repositories' => [
                ],
            ],
            'single plugin returning true' => [
                'expected' => true,
                'repositories' => [
                    $pluginTrue,
                ],
            ],
            'single plugin returning false' => [
                'expected' => false,
                'repositories' => [
                    $pluginFalse,
                ],
            ],
            'single tool returning true' => [
                'expected' => true,
                'repositories' => [
                    $toolTrue,
                ],
            ],
            'single tool returning false' => [
                'expected' => false,
                'repositories' => [
                    $toolFalse,
                ],
            ],
            'single enriching returning true' => [
                'expected' => true,
                'repositories' => [
                    $enrichingTrue,
                ],
            ],
            'single enriching returning false' => [
                'expected' => false,
                'repositories' => [
                    $enrichingFalse,
                ],
            ],
        ];
    }

    /** @dataProvider isFreshProvider */
    public function testIsFresh(bool $expected, array $repositories): void
    {
        $compound = new CompoundRepository(...$repositories);

        self::assertSame($expected, $compound->isFresh());
    }

    public function testRefresh(): void
    {
        $repositories = [];
        for ($i = 0; $i < 2; $i++) {
            $repository = $this->getMockForAbstractClass(PluginVersionProvidingInterface::class);
            $repository->expects(self::once())->method('refresh');

            $repositories[] = $repository;
        }

        for ($i = 0; $i < 2; $i++) {
            $repository = $this->getMockForAbstractClass(ToolVersionProvidingInterface::class);
            $repository->expects(self::once())->method('refresh');

            $repositories[] = $repository;
        }

        for ($i = 0; $i < 2; $i++) {
            $repository = $this->getMockForAbstractClass(ToolVersionEnrichingRepositoryInterface::class);
            $repository->expects(self::once())->method('refresh');

            $repositories[] = $repository;
        }

        $compound = new CompoundRepository(...$repositories);

        $compound->refresh();
    }

    public function supportsProvider(): array
    {
        $version = $this->getMockForAbstractClass(ToolVersionInterface::class);

        return [
            'without children' => [
                'expected' => false,
                'version' => $version,
                'repositories' => [],
            ],
            'short circuit after first true' => [
                'expected' => true,
                'version' => $version,
                'repositories' => Closure::fromCallable(function (): array {
                    $true = $this->getMockForAbstractClass(ToolVersionEnrichingRepositoryInterface::class);
                    $true->expects(self::once())->method('supports')->willReturn(true);
                    $never = $this->getMockForAbstractClass(ToolVersionEnrichingRepositoryInterface::class);
                    $never->expects(self::never())->method('supports');

                    return [$true, $never];
                })->__invoke(),
            ],
            'false if none matches' => [
                'expected' => false,
                'version' => $version,
                'repositories' => Closure::fromCallable(function (): array {
                    $false1 = $this->getMockForAbstractClass(ToolVersionEnrichingRepositoryInterface::class);
                    $false1->expects(self::once())->method('supports')->willReturn(false);
                    $false2 = $this->getMockForAbstractClass(ToolVersionEnrichingRepositoryInterface::class);
                    $false2->expects(self::once())->method('supports')->willReturn(false);

                    return [$false1, $false2];
                })->__invoke(),
            ],
        ];
    }

    /** @dataProvider supportsProvider */
    public function testSupports(bool $expected, ToolVersionInterface $version, array $repositories): void
    {
        $compound = new CompoundRepository(...$repositories);

        self::assertSame($expected, $compound->supports($version));
    }

    public function testEnrichCallsAllSupportingProviders(): void
    {
        $version = $this->getMockForAbstractClass(ToolVersionInterface::class);

        $supporting1 = $this->getMockForAbstractClass(ToolVersionEnrichingRepositoryInterface::class);
        $supporting1->expects(self::once())->method('supports')->with($version)->willReturn(true);
        $supporting1->expects(self::once())->method('enrich')->with($version);
        $unsupporting = $this->getMockForAbstractClass(ToolVersionEnrichingRepositoryInterface::class);
        $unsupporting->expects(self::once())->method('supports')->willReturn(false);
        $unsupporting->expects(self::never())->method('enrich');
        $supporting2 = $this->getMockForAbstractClass(ToolVersionEnrichingRepositoryInterface::class);
        $supporting2->expects(self::once())->method('supports')->with($version)->willReturn(true);
        $supporting2->expects(self::once())->method('enrich')->with($version);

        $repositories = [$supporting1, $unsupporting, $supporting2];

        $compound = new CompoundRepository(...$repositories);
        $compound->enrich($version);
    }

    public function testIterateTools(): void
    {
        $versions = [
            $version1 = $this->getMockForAbstractClass(ToolVersionInterface::class),
            $version2 = $this->getMockForAbstractClass(ToolVersionInterface::class),
            $version3 = $this->getMockForAbstractClass(ToolVersionInterface::class),
            $version4 = $this->getMockForAbstractClass(ToolVersionInterface::class),
        ];

        $provider1 = $this->getMockForAbstractClass(ToolVersionProvidingRepositoryInterface::class);
        $provider1->expects(self::once())->method('getToolIterator')->will($this->generate($version1, $version2));
        $provider2 = $this->getMockForAbstractClass(ToolVersionProvidingRepositoryInterface::class);
        $provider2->expects(self::once())->method('getToolIterator')->will($this->generate($version3, $version4));

        $versionArgs = array_map(function (ToolVersionInterface $version): array {
            return [$version];
        }, $versions);
        $enricher = $this->getMockForAbstractClass(ToolVersionEnrichingRepositoryInterface::class);
        $enricher->expects(self::exactly(4))->method('supports')->withConsecutive(...$versionArgs)->willReturn(true);
        $enricher->expects(self::exactly(4))->method('enrich')->withConsecutive(...$versionArgs);

        $compound = new CompoundRepository($provider1, $provider2, $enricher);

        self::assertSame($versions, iterator_to_array($compound->getToolIterator()));
    }

    public function testIteratePlugins(): void
    {
        $versions = [
            $version1 = $this->getMockForAbstractClass(PluginVersionInterface::class),
            $version2 = $this->getMockForAbstractClass(PluginVersionInterface::class),
            $version3 = $this->getMockForAbstractClass(PluginVersionInterface::class),
            $version4 = $this->getMockForAbstractClass(PluginVersionInterface::class),
        ];

        $provider1 = $this->getMockForAbstractClass(PluginVersionProvidingInterface::class);
        $provider1->expects(self::once())->method('getPluginIterator')->will($this->generate($version1, $version2));
        $provider2 = $this->getMockForAbstractClass(PluginVersionProvidingInterface::class);
        $provider2->expects(self::once())->method('getPluginIterator')->will($this->generate($version3, $version4));

        $compound = new CompoundRepository($provider1, $provider2);

        self::assertSame($versions, iterator_to_array($compound->getPluginIterator()));
    }

    public function testSetLogger(): void
    {
        $logger = $this->getMockForAbstractClass(LoggerInterface::class);

        $repositories = [];
        for ($i = 0; $i < 3; $i++) {
            if ($i === 1) {
                $repository = $this->getMockForAbstractClass(PluginVersionProvidingRepositoryInterface::class);
            } else {
                $repository = $this->getMockForAbstractClass(PluginVersionProvidingInterface::class);
                $repository->expects(self::once())->method('setLogger')->with($logger);
            }

            $repositories[] = $repository;
        }

        for ($i = 0; $i < 3; $i++) {
            if ($i === 1) {
                $repository = $this->getMockForAbstractClass(ToolVersionProvidingRepositoryInterface::class);
            } else {
                $repository = $this->getMockForAbstractClass(ToolVersionProvidingInterface::class);
                $repository->expects(self::once())->method('setLogger')->with($logger);
            }

            $repositories[] = $repository;
        }

        for ($i = 0; $i < 3; $i++) {
            if ($i === 1) {
                $repository = $this->getMockForAbstractClass(ToolVersionEnrichingRepositoryInterface::class);
            } else {
                $repository = $this->getMockForAbstractClass(ToolVersionEnrichingInterface::class);
                $repository->expects(self::once())->method('setLogger')->with($logger);
            }

            $repositories[] = $repository;
        }

        $compound = new CompoundRepository(...$repositories);

        $compound->setLogger($logger);
    }

    protected function generate(...$values): ReturnCallback
    {
        return $this->returnCallback(function () use ($values) {
            foreach ($values as $value) {
                yield $value;
            }
        });
    }
}
