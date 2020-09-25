<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test;

use Phpcq\RepositoryBuilder\RepositoryDiffBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * @covers \Phpcq\RepositoryBuilder\RepositoryBuilder
 */
final class RepositoryDiffBuilderTest extends TestCase
{
    public function generateProvider(): array
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

    /** @dataProvider generateProvider */
    public function testGeneratesDiffCorrectly(string $expected, string $before, string $after): void
    {
        $tempDir = sys_get_temp_dir() . '/' . uniqid();
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
