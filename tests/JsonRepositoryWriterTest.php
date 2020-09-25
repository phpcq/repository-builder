<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test;

use Phpcq\RepositoryBuilder\JsonRepositoryWriter;
use Phpcq\RepositoryDefinition\Tool\Tool;
use Phpcq\RepositoryDefinition\Tool\ToolHash;
use Phpcq\RepositoryDefinition\Tool\ToolRequirements;
use Phpcq\RepositoryDefinition\Tool\ToolVersion;
use Phpcq\RepositoryDefinition\VersionRequirement;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @covers \Phpcq\RepositoryBuilder\JsonRepositoryWriter
 */
final class JsonRepositoryWriterTest extends TestCase
{
    /** @SuppressWarnings(PHPMD.ExcessiveMethodLength) */
    public function testWrite(): void
    {
        $tool1 = new Tool('tool1');

        $req1 = new ToolRequirements();
        $req1->getPhpRequirements()->add(new VersionRequirement('php', '7.1.0'));
        $req1->getPhpRequirements()->add(new VersionRequirement('ext-foo', '*'));
        $tool1->addVersion(new ToolVersion(
            'tool1',
            '1.0.0',
            'https://example.org/some-1.0.0.phar',
            $req1,
            ToolHash::create(ToolHash::SHA_512, 'hash1'),
            'https://example.org/some-1.0.0.phar.sig',
        ));
        $req2 = new ToolRequirements();
        $req2->getPhpRequirements()->add(new VersionRequirement('php', '7.3.0'));
        $req2->getPhpRequirements()->add(new VersionRequirement('ext-foo', '*'));
        $tool1->addVersion(new ToolVersion(
            'tool1',
            '2.0.0',
            'https://example.org/some-2.0.0.phar',
            $req2,
            ToolHash::create(ToolHash::SHA_512, 'hash2'),
            'https://example.org/some-2.0.0.phar.sig',
        ));

        $tool2 = new Tool('tool2');

        $req3 = new ToolRequirements();
        $req3->getPhpRequirements()->add(new VersionRequirement('php', '7.1.1'));
        $req3->getPhpRequirements()->add(new VersionRequirement('ext-foo', '*'));
        $tool2->addVersion(new ToolVersion(
            'tool2',
            '1.0.0',
            'https://example.org/another-1.0.0.phar',
            $req3,
            ToolHash::create(ToolHash::SHA_512, 'hash3'),
            'https://example.org/another-1.0.0.phar.sig',
        ));
        $req4 = new ToolRequirements();
        $req4->getPhpRequirements()->add(new VersionRequirement('php', '7.3.1'));
        $req4->getPhpRequirements()->add(new VersionRequirement('ext-foo', '*'));
        $tool2->addVersion(new ToolVersion(
            'tool2',
            '2.0.0',
            'https://example.org/another-2.0.0.phar',
            $req4,
            ToolHash::create(ToolHash::SHA_512, 'hash4'),
            'https://example.org/another-2.0.0.phar.sig',
        ));

        $tempDir = sys_get_temp_dir() . '/' . uniqid();
        $fileSystem = new Filesystem();
        $fileSystem->mkdir($tempDir);
        try {
            $writer = new JsonRepositoryWriter($tempDir);
            $writer->writeTool($tool1);
            $writer->writeTool($tool2);
            $writer->save();

            $this->assertFileExists($tempDir . '/repository.json');
            $this->assertFileExists($tempDir . '/tool1-tool.json');
            $this->assertFileExists($tempDir . '/tool2-tool.json');

            $this->assertRepositoryFileMatches([
                'tools' => [
                    'tool1' => [
                        [
                            'version'      => '1.0.0',
                            'url'          => 'https://example.org/some-1.0.0.phar',
                            'requirements' => [
                                'php' => [
                                    'php'      => '7.1.0',
                                    'ext-foo'  => '*',
                                ],
                            ],
                            'checksum'     => ['type' => 'sha-512', 'value' => 'hash1'],
                            'signature'    => 'https://example.org/some-1.0.0.phar.sig',
                        ],
                        [
                            'version'      => '2.0.0',
                            'url'          => 'https://example.org/some-2.0.0.phar',
                            'requirements' => [
                                'php' => [
                                    'php'      => '7.3.0',
                                    'ext-foo'  => '*',
                                ],
                            ],
                            'checksum'     => ['type' => 'sha-512', 'value' => 'hash2'],
                            'signature'    => 'https://example.org/some-2.0.0.phar.sig',
                        ],
                    ],
                ],
            ], $tempDir . '/tool1-tool.json');
            $this->assertRepositoryFileMatches([
                'tools' => [
                    'tool2' => [
                        [
                            'version'      => '1.0.0',
                            'url'          => 'https://example.org/another-1.0.0.phar',
                            'requirements' => [
                                'php' => [
                                    'php'      => '7.1.1',
                                    'ext-foo'  => '*',
                                ],
                            ],
                            'checksum'     => ['type' => 'sha-512', 'value' => 'hash3'],
                            'signature'    => 'https://example.org/another-1.0.0.phar.sig',
                        ],
                        [
                            'version'      => '2.0.0',
                            'url'          => 'https://example.org/another-2.0.0.phar',
                            'requirements' => [
                                'php' => [
                                    'php'      => '7.3.1',
                                    'ext-foo'  => '*',
                                ],
                            ],
                            'checksum'     => ['type' => 'sha-512', 'value' => 'hash4'],
                            'signature'    => 'https://example.org/another-2.0.0.phar.sig',
                        ],
                    ],
                ],
            ], $tempDir . '/tool2-tool.json');

            $this->assertSame([
                'includes' => [
                    [
                        'url'      => 'tool1-tool.json',
                        'checksum' => [
                            'type'  => 'sha-512',
                            'value' => hash_file('sha512', $tempDir . '/tool1-tool.json'),
                        ],
                    ],
                    [
                        'url'      => 'tool2-tool.json',
                        'checksum' => [
                            'type'  => 'sha-512',
                            'value' => hash_file('sha512', $tempDir . '/tool2-tool.json'),
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
