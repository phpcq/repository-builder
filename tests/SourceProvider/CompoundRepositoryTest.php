<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\SourceProvider;

use Closure;
use InvalidArgumentException;
use Phpcq\RepositoryBuilder\SourceProvider\CompoundRepository;
use Phpcq\RepositoryBuilder\SourceProvider\PluginVersionProvidingRepositoryInterface;
use Phpcq\RepositoryBuilder\SourceProvider\SourceRepositoryInterface;
use Phpcq\RepositoryBuilder\SourceProvider\Tool\ToolVersionEnrichingRepositoryInterface;
use Phpcq\RepositoryBuilder\SourceProvider\Tool\ToolVersionProvidingRepositoryInterface;
use Phpcq\RepositoryBuilder\Test\ConsecutiveAssertTrait;
use Phpcq\RepositoryBuilder\Test\SourceProvider\MockRepositoryInterface\PluginVersionProvidingInterface;
use Phpcq\RepositoryBuilder\Test\SourceProvider\MockRepositoryInterface\ToolVersionEnrichingInterface;
use Phpcq\RepositoryBuilder\Test\SourceProvider\MockRepositoryInterface\ToolVersionProvidingInterface;
use Phpcq\RepositoryDefinition\Plugin\PluginVersionInterface;
use Phpcq\RepositoryDefinition\Tool\ToolVersionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/** @SuppressWarnings(PHPMD.TooManyPublicMethods) */
#[CoversClass(CompoundRepository::class)]
class CompoundRepositoryTest extends TestCase
{
    use ConsecutiveAssertTrait;

    public function testThrowsOnUnsupported(): void
    {
        $mock = $this->getMockBuilder(SourceRepositoryInterface::class)->getMock();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown provider type ' . get_class($mock));

        new CompoundRepository($mock);
    }

    public function testAcceptsPluginProvider(): void
    {
        new CompoundRepository($this->getMockBuilder(PluginVersionProvidingInterface::class)->getMock());

        $this->addToAssertionCount(1);
    }

    public function testAcceptsToolProvider(): void
    {
        new CompoundRepository($this->getMockBuilder(ToolVersionProvidingInterface::class)->getMock());

        $this->addToAssertionCount(1);
    }

    public function testAcceptsEnrichingProvider(): void
    {
        new CompoundRepository($this->getMockBuilder(ToolVersionEnrichingRepositoryInterface::class)->getMock());

        $this->addToAssertionCount(1);
    }

    public static function isFreshProvider(): array
    {
        $pluginTrue = static function (TestCase $test) {
            $pluginTrue = $test->getMockBuilder(PluginVersionProvidingInterface::class)->getMock();
            $pluginTrue->method('isFresh')->willReturn(true);
            return $pluginTrue;
        };
        $pluginFalse = static function (TestCase $test) {
            $pluginFalse = $test->getMockBuilder(PluginVersionProvidingInterface::class)->getMock();
            $pluginFalse->method('isFresh')->willReturn(false);
            return $pluginFalse;
        };
        $toolTrue = static function (TestCase $test) {
            $toolTrue = $test->getMockBuilder(ToolVersionProvidingInterface::class)->getMock();
            $toolTrue->method('isFresh')->willReturn(true);
            return $toolTrue;
        };
        $toolFalse = static function (TestCase $test) {
            $toolFalse = $test->getMockBuilder(ToolVersionProvidingInterface::class)->getMock();
            $toolFalse->method('isFresh')->willReturn(false);
            return $toolFalse;
        };
        $enrichingTrue = static function (TestCase $test) {
            $enrichingTrue = $test->getMockBuilder(ToolVersionEnrichingRepositoryInterface::class)->getMock();
            $enrichingTrue->method('isFresh')->willReturn(true);
            return $enrichingTrue;
        };
        $enrichingFalse = static function (TestCase $test) {
            $enrichingFalse = $test->getMockBuilder(ToolVersionEnrichingRepositoryInterface::class)->getMock();
            $enrichingFalse->method('isFresh')->willReturn(false);
            return $enrichingFalse;
        };

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

    #[DataProvider('isFreshProvider')]
    public function testIsFresh(bool $expected, array $repositories): void
    {
        $compound = new CompoundRepository(...array_map(fn($callback) => $callback($this), $repositories));

        self::assertSame($expected, $compound->isFresh());
    }

    public function testRefresh(): void
    {
        $repositories = [];
        for ($i = 0; $i < 2; $i++) {
            $repository = $this->getMockBuilder(PluginVersionProvidingInterface::class)->getMock();
            $repository->expects($this->once())->method('refresh');

            $repositories[] = $repository;
        }

        for ($i = 0; $i < 2; $i++) {
            $repository = $this->getMockBuilder(ToolVersionProvidingInterface::class)->getMock();
            $repository->expects($this->once())->method('refresh');

            $repositories[] = $repository;
        }

        for ($i = 0; $i < 2; $i++) {
            $repository = $this->getMockBuilder(ToolVersionEnrichingRepositoryInterface::class)->getMock();
            $repository->expects($this->once())->method('refresh');

            $repositories[] = $repository;
        }

        $compound = new CompoundRepository(...$repositories);

        $compound->refresh();
    }

    public static function supportsProvider(): array
    {

        return [
            'without children' => [
                'expected' => false,
                'repositories' => function (TestCase $test): array {
                    return [];
                },
            ],
            'short circuit after first true' => [
                'expected' => true,
                'repositories' => function (TestCase $test): array {
                    $true = $test->getMockBuilder(ToolVersionEnrichingRepositoryInterface::class)->getMock();
                    $true->expects($test->once())->method('supports')->willReturn(true);
                    $never = $test->getMockBuilder(ToolVersionEnrichingRepositoryInterface::class)->getMock();
                    $never->expects($test->never())->method('supports');

                    return [$true, $never];
                },
            ],
            'false if none matches' => [
                'expected' => false,
                'repositories' => function (TestCase $test): array {
                    $false1 = $test->getMockBuilder(ToolVersionEnrichingRepositoryInterface::class)->getMock();
                    $false1->expects($test->once())->method('supports')->willReturn(false);
                    $false2 = $test->getMockBuilder(ToolVersionEnrichingRepositoryInterface::class)->getMock();
                    $false2->expects($test->once())->method('supports')->willReturn(false);

                    return [$false1, $false2];
                },
            ],
        ];
    }

    #[DataProvider('supportsProvider')]
    public function testSupports(bool $expected, callable $repositories): void
    {
        $version = $this->getMockBuilder(ToolVersionInterface::class)->getMock();
        $compound = new CompoundRepository(...$repositories($this));

        self::assertSame($expected, $compound->supports($version));
    }

    public function testEnrichCallsAllSupportingProviders(): void
    {
        $version = $this->getMockBuilder(ToolVersionInterface::class)->getMock();

        $supporting1 = $this->getMockBuilder(ToolVersionEnrichingRepositoryInterface::class)->getMock();
        $supporting1->expects($this->once())->method('supports')->with($version)->willReturn(true);
        $supporting1->expects($this->once())->method('enrich')->with($version);
        $unsupporting = $this->getMockBuilder(ToolVersionEnrichingRepositoryInterface::class)->getMock();
        $unsupporting->expects($this->once())->method('supports')->willReturn(false);
        $unsupporting->expects($this->never())->method('enrich');
        $supporting2 = $this->getMockBuilder(ToolVersionEnrichingRepositoryInterface::class)->getMock();
        $supporting2->expects($this->once())->method('supports')->with($version)->willReturn(true);
        $supporting2->expects($this->once())->method('enrich')->with($version);

        $repositories = [$supporting1, $unsupporting, $supporting2];

        $compound = new CompoundRepository(...$repositories);
        $compound->enrich($version);
    }

    public function testIterateTools(): void
    {
        $versions = [
            $version1 = $this->getMockBuilder(ToolVersionInterface::class)->getMock(),
            $version2 = $this->getMockBuilder(ToolVersionInterface::class)->getMock(),
            $version3 = $this->getMockBuilder(ToolVersionInterface::class)->getMock(),
            $version4 = $this->getMockBuilder(ToolVersionInterface::class)->getMock(),
        ];

        $provider1 = $this->getMockBuilder(ToolVersionProvidingRepositoryInterface::class)->getMock();
        $provider1->expects($this->once())->method('getToolIterator')->will($this->generate($version1, $version2));
        $provider2 = $this->getMockBuilder(ToolVersionProvidingRepositoryInterface::class)->getMock();
        $provider2->expects($this->once())->method('getToolIterator')->will($this->generate($version3, $version4));

        $versionArgs2 = array_map(
            static fn (ToolVersionInterface $version): array => ['arguments' => [$version], 'return' => true],
            $versions
        );
        $versionArgs3 = array_map(
            static fn (ToolVersionInterface $version): array => ['arguments' => [$version]],
            $versions
        );
        $enricher = $this->getMockBuilder(ToolVersionEnrichingRepositoryInterface::class)->getMock();
        $enricher
            ->expects($this->exactly(4))
            ->method('supports')
            ->will($this->handleConsecutive(...$versionArgs2));
        $enricher
            ->expects($this->exactly(4))
            ->method('enrich')
            ->will($this->handleConsecutive(...$versionArgs3));

        $compound = new CompoundRepository($provider1, $provider2, $enricher);

        self::assertSame($versions, iterator_to_array($compound->getToolIterator()));
    }

    public function testIteratePlugins(): void
    {
        $versions = [
            $version1 = $this->getMockBuilder(PluginVersionInterface::class)->getMock(),
            $version2 = $this->getMockBuilder(PluginVersionInterface::class)->getMock(),
            $version3 = $this->getMockBuilder(PluginVersionInterface::class)->getMock(),
            $version4 = $this->getMockBuilder(PluginVersionInterface::class)->getMock(),
        ];

        $provider1 = $this->getMockBuilder(PluginVersionProvidingInterface::class)->getMock();
        $provider1->expects($this->once())->method('getPluginIterator')->will($this->generate($version1, $version2));
        $provider2 = $this->getMockBuilder(PluginVersionProvidingInterface::class)->getMock();
        $provider2->expects($this->once())->method('getPluginIterator')->will($this->generate($version3, $version4));

        $compound = new CompoundRepository($provider1, $provider2);

        self::assertSame($versions, iterator_to_array($compound->getPluginIterator()));
    }

    public function testSetLogger(): void
    {
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $repositories = [];
        for ($i = 0; $i < 3; $i++) {
            if ($i === 1) {
                $repository = $this->getMockBuilder(PluginVersionProvidingRepositoryInterface::class)->getMock();
            } else {
                $repository = $this->getMockBuilder(PluginVersionProvidingInterface::class)->getMock();
                $repository->expects($this->once())->method('setLogger')->with($logger);
            }

            $repositories[] = $repository;
        }

        for ($i = 0; $i < 3; $i++) {
            if ($i === 1) {
                $repository = $this->getMockBuilder(ToolVersionProvidingRepositoryInterface::class)->getMock();
            } else {
                $repository = $this->getMockBuilder(ToolVersionProvidingInterface::class)->getMock();
                $repository->expects($this->once())->method('setLogger')->with($logger);
            }

            $repositories[] = $repository;
        }

        for ($i = 0; $i < 3; $i++) {
            if ($i === 1) {
                $repository = $this->getMockBuilder(ToolVersionEnrichingRepositoryInterface::class)->getMock();
            } else {
                $repository = $this->getMockBuilder(ToolVersionEnrichingInterface::class)->getMock();
                $repository->expects($this->once())->method('setLogger')->with($logger);
            }

            $repositories[] = $repository;
        }

        $compound = new CompoundRepository(...$repositories);

        $compound->setLogger($logger);
    }

    protected function generate(...$values): ReturnCallback
    {
        return new ReturnCallback(static function () use ($values) {
            foreach ($values as $value) {
                yield $value;
            }
        });
    }
}
