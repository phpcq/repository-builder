<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test;

use Phpcq\RepositoryBuilder\JsonRepositoryWriter;
use Phpcq\RepositoryBuilder\Repository\InlineBootstrap;
use Phpcq\RepositoryBuilder\Repository\Tool;
use Phpcq\RepositoryBuilder\Repository\ToolHash;
use Phpcq\RepositoryBuilder\Repository\ToolVersion;
use Phpcq\RepositoryBuilder\Repository\VersionRequirement;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @covers \Phpcq\RepositoryBuilder\JsonRepositoryWriter
 */
final class JsonRepositoryWriterTest extends TestCase
{
    public function testWrite(): void
    {
        $tool1 = new Tool('tool1');
        $tool1->addVersion(new ToolVersion(
            'tool1',
            '1.0.0',
            'https://example.org/some-1.0.0.phar',
            [
                new VersionRequirement('php', '7.1.0'),
                new VersionRequirement('ext-foo', '*'),
            ],
            new ToolHash(ToolHash::SHA_512, 'hash1'),
            'https://example.org/some-1.0.0.phar.sig',
            $bootstrap1 = new InlineBootstrap('1.0.0', 'bootstrap 1')
        ));
        $tool1->addVersion(new ToolVersion(
            'tool1',
            '2.0.0',
            'https://example.org/some-2.0.0.phar',
            [
                new VersionRequirement('php', '7.3.0'),
                new VersionRequirement('ext-foo', '*'),
            ],
            new ToolHash(ToolHash::SHA_512, 'hash2'),
            'https://example.org/some-2.0.0.phar.sig',
            $bootstrap1,
        ));

        $tool2 = new Tool('tool2');
        $tool2->addVersion(new ToolVersion(
            'tool2',
            '1.0.0',
            'https://example.org/another-1.0.0.phar',
            [
                new VersionRequirement('php', '7.1.1'),
                new VersionRequirement('ext-foo', '*'),
            ],
            new ToolHash(ToolHash::SHA_512, 'hash3'),
            'https://example.org/another-1.0.0.phar.sig',
            new InlineBootstrap('1.0.0', 'bootstrap 2'),
        ));
        $tool2->addVersion(new ToolVersion(
            'tool2',
            '2.0.0',
            'https://example.org/another-2.0.0.phar',
            [
                new VersionRequirement('php', '7.3.1'),
                new VersionRequirement('ext-foo', '*'),
            ],
            new ToolHash(ToolHash::SHA_512, 'hash4'),
            'https://example.org/another-2.0.0.phar.sig',
            new InlineBootstrap('1.0.0', 'bootstrap 3'),
        ));

        $tempDir = sys_get_temp_dir() . '/' . uniqid();
        $fileSystem = new Filesystem();
        $fileSystem->mkdir($tempDir);
        try {
            $writer = new JsonRepositoryWriter($tempDir);
            $writer->write($tool1);
            $writer->write($tool2);
            $writer->save();

            $this->assertFileExists($tempDir . '/repository.json');
            $this->assertFileExists($tempDir . '/tool1.json');
            $this->assertFileExists($tempDir . '/tool2.json');

            $this->assertRepositoryFileMatches([
                'bootstraps' => [
                    'bootstrap-0' => [
                        'plugin-version' => '1.0.0',
                        'type'           => 'inline',
                        'code'           => 'bootstrap 1'
                    ],
                ],
                'phars' => [
                    'tool1' => [
                        [
                            'version'      => '1.0.0',
                            'phar-url'     => 'https://example.org/some-1.0.0.phar',
                            'bootstrap'    => 'bootstrap-0',
                            'requirements' => [
                                'php'      => '7.1.0',
                                'ext-foo'  => '*',
                            ],
                            'hash'         => ['type' => 'sha-512', 'value' => 'hash1'],
                            'signature'    => 'https://example.org/some-1.0.0.phar.sig',
                        ],
                        [
                            'version'      => '2.0.0',
                            'phar-url'     => 'https://example.org/some-2.0.0.phar',
                            'bootstrap'    => 'bootstrap-0',
                            'requirements' => [
                                'php'      => '7.3.0',
                                'ext-foo'  => '*',
                            ],
                            'hash'         => ['type' => 'sha-512', 'value' => 'hash2'],
                            'signature'    => 'https://example.org/some-2.0.0.phar.sig',
                        ],
                    ],
                ],
            ], $tempDir . '/tool1.json');
            $this->assertRepositoryFileMatches([
                'bootstraps' => [
                    'bootstrap-0' => [
                        'plugin-version' => '1.0.0',
                        'type'           => 'inline',
                        'code'           => 'bootstrap 2'
                    ],
                    'bootstrap-1' => [
                        'plugin-version' => '1.0.0',
                        'type'           => 'inline',
                        'code'           => 'bootstrap 3'
                    ],
                ],
                'phars' => [
                    'tool2' => [
                        [
                            'version'      => '1.0.0',
                            'phar-url'     => 'https://example.org/another-1.0.0.phar',
                            'bootstrap'    => 'bootstrap-0',
                            'requirements' => [
                                'php'      => '7.1.1',
                                'ext-foo'  => '*',
                            ],
                            'hash'         => ['type' => 'sha-512', 'value' => 'hash3'],
                            'signature'    => 'https://example.org/another-1.0.0.phar.sig',
                        ],
                        [
                            'version'      => '2.0.0',
                            'phar-url'     => 'https://example.org/another-2.0.0.phar',
                            'bootstrap'    => 'bootstrap-1',
                            'requirements' => [
                                'php'      => '7.3.1',
                                'ext-foo'  => '*',
                            ],
                            'hash'         => ['type' => 'sha-512', 'value' => 'hash4'],
                            'signature'    => 'https://example.org/another-2.0.0.phar.sig',
                        ],
                    ],
                ],
            ], $tempDir . '/tool2.json');

            $this->assertSame([
                'bootstraps' => [],
                'phars' => [
                    'tool1' => [
                        'url'      => './tool1.json',
                        'checksum' => [
                            'type'  => 'sha-512',
                            'value' => hash_file('sha512', $tempDir . '/tool1.json'),
                        ],
                    ],
                    'tool2' => [
                        'url'      => './tool2.json',
                        'checksum' => [
                            'type'  => 'sha-512',
                            'value' => hash_file('sha512', $tempDir . '/tool2.json'),
                        ],
                    ],
                ],
            ], json_decode(file_get_contents($tempDir . '/repository.json'), true));
        } finally {
            $fileSystem->remove($tempDir);
        }
    }

    private function assertRepositoryFileMatches(array $data, string $filePath): void
    {
        $this->assertSame($data, json_decode(file_get_contents($filePath), true));
    }
}
