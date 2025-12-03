<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test;

use Phpcq\RepositoryBuilder\RepositoryBuilder;
use Phpcq\RepositoryBuilder\RepositoryDiffBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

#[CoversClass(RepositoryBuilder::class)]
final class RepositoryDiffBuilderTest extends TestCase
{
    public static function generateProvider(): array
    {
        $tests = [];
        foreach (Finder::create()->in(__DIR__ . '/fixtures/repository-diff')->directories()->depth(0) as $directory) {
            $tests[$directory->getBasename()] = [
                'expected' => file_get_contents($directory->getRealPath() . '/diff.txt'),
                'before'   => $directory->getRealPath() . '/before',
                'after'    => $directory->getRealPath() . '/after',
            ];
        }

        return $tests;
    }

    #[DataProvider('generateProvider')]
    public function testGeneratesDiffCorrectly(string $expected, string $before, string $after): void
    {
        $tempDir = sys_get_temp_dir() . '/' . uniqid(more_entropy: false);
        $fileSystem = new Filesystem();
        $fileSystem->mkdir($tempDir);
        try {
            $fileSystem->mirror($before, $tempDir, null, ['override' => true]);
            $builder = new RepositoryDiffBuilder($tempDir);
            $fileSystem->mirror($after, $tempDir, null, ['override' => true]);
            $diff = $builder->generate();
            $this->assertEquals($expected, $diff->asString(''));
        } finally {
            $fileSystem->remove($tempDir);
        }
    }
}
