<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\SourceProvider\Plugin\Github;

use Phpcq\RepositoryBuilder\Api\GithubClient;
use Phpcq\RepositoryBuilder\SourceProvider\Plugin\Github\JsonEntry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JsonEntry::class)]
final class JsonEntryTest extends TestCase
{
    public function testPlainGetters(): void
    {
        $client = $this->getMockBuilder(GithubClient::class)->disableOriginalConstructor()->getMock();
        $client->expects($this->never())->method('fetchFile');

        $entry = new JsonEntry('repository-name', 'tag-name', '1.0.0', $client);

        self::assertSame('repository-name', $entry->getRepository());
        self::assertSame('tag-name', $entry->getTagName());
        self::assertSame('1.0.0', $entry->getVersion());
    }

    public function testLoadingSucceeds(): void
    {
        $contents = [
            'name'         => 'dummy',
            'type'         => 'php-file',
            'url'          => 'src/file.php',
            'signature'    => 'src/file.php.asc',
            'api-version'  => '1.0.0',
            'requirements' => [],
        ];

        $client = $this->getMockBuilder(GithubClient::class)->disableOriginalConstructor()->getMock();
        $client
            ->expects($this->once())
            ->method('fetchFile')
            ->with('repository-name', 'tag-name', 'phpcq-plugin.json')
            ->willReturn($contents);
        $client
            ->method('fileUri')
            ->willReturnCallback(function (string $repository, string $refSpec, string $filePath): string {
                return sprintf(
                    'https://example.org/%1$s/%2$s/%3$s',
                    $repository,
                    $refSpec,
                    $filePath
                );
            });

        $entry = new JsonEntry('repository-name', 'tag-name', '1.0.0', $client);

        self::assertSame($contents, $entry->getContents());
        self::assertSame('src/file.php', $entry->getPluginUrl(false));
        self::assertSame(
            'https://example.org/repository-name/tag-name/src/file.php',
            $entry->getPluginUrl(true)
        );
        self::assertSame('src/file.php.asc', $entry->getSignatureUrl(false));
        self::assertSame(
            'https://example.org/repository-name/tag-name/src/file.php.asc',
            $entry->getSignatureUrl(true)
        );
    }

    public function testLoadingWithoutSignatureReturnsNull(): void
    {
        $contents = [
            'name'         => 'dummy',
            'type'         => 'php-file',
            'url'          => 'src/file.php',
            'api-version'  => '1.0.0',
            'requirements' => [],
        ];

        $client = $this->getMockBuilder(GithubClient::class)->disableOriginalConstructor()->getMock();
        $client
            ->expects($this->once())
            ->method('fetchFile')
            ->with('repository-name', 'tag-name', 'phpcq-plugin.json')
            ->willReturn($contents);
        $client->expects($this->never())->method('fileUri');

        $entry = new JsonEntry('repository-name', 'tag-name', '1.0.0', $client);
        self::assertNull($entry->getSignatureUrl(false));
        self::assertNull($entry->getSignatureUrl(true));
    }

    public function testLoadingWithAbsoluteUrlDoesNotAlterUrl(): void
    {
        $contents = [
            'name'         => 'dummy',
            'type'         => 'php-file',
            'url'          => 'https://example.org/repository-name/tag-name/src/file.php',
            'api-version'  => '1.0.0',
            'requirements' => [],
        ];

        $client = $this->getMockBuilder(GithubClient::class)->disableOriginalConstructor()->getMock();
        $client
            ->expects($this->once())
            ->method('fetchFile')
            ->with('repository-name', 'tag-name', 'phpcq-plugin.json')
            ->willReturn($contents);
        $client->expects($this->never())->method('fileUri');

        $entry = new JsonEntry('repository-name', 'tag-name', '1.0.0', $client);
        self::assertSame(
            'https://example.org/repository-name/tag-name/src/file.php',
            $entry->getPluginUrl(false)
        );
        self::assertSame(
            'https://example.org/repository-name/tag-name/src/file.php',
            $entry->getPluginUrl(true)
        );
    }
}
