<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\SourceProvider\Plugin\Github;

use Phpcq\RepositoryBuilder\Api\GithubClient;
use Phpcq\RepositoryBuilder\SourceProvider\Plugin\Github\JsonLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JsonLoader::class)]
final class JsonLoaderTest extends TestCase
{
    public function testLoadingSucceeds(): void
    {
        $contents = [
            ['ref' => 'refs/tags/1.0.0'],
            ['ref' => 'refs/tags/invalid-tag-that-will-get-filtered-out'],
            ['ref' => 'refs/tags/2.0.0'],
        ];
        $githubClient = $this->getMockBuilder(GithubClient::class)->disableOriginalConstructor()->getMock();
        $githubClient
            ->expects($this->never())->method('fetchFile');
        $githubClient
            ->expects($this->once())
            ->method('fetchTags')
            ->with('repository-name')
            ->willReturn($contents);

        $loader = new JsonLoader($githubClient, 'repository-name');

        $entries = iterator_to_array($loader->getJsonFileIterator());

        self::assertCount(2, $entries);
        self::assertSame('repository-name', $entries[0]->getRepository());
        self::assertSame('1.0.0', $entries[0]->getTagName());
        self::assertSame('1.0.0.0', $entries[0]->getVersion());
        self::assertSame('repository-name', $entries[1]->getRepository());
        self::assertSame('2.0.0', $entries[1]->getTagName());
        self::assertSame('2.0.0.0', $entries[1]->getVersion());
    }
}
