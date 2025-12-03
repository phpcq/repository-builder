<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\Api;

use Closure;
use Phpcq\RepositoryBuilder\Api\GithubClient;
use Phpcq\RepositoryBuilder\Exception\DataNotAvailableException;
use Phpcq\RepositoryBuilder\Test\ConsecutiveAssertTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

#[CoversClass(GithubClient::class)]
class GithubClientTest extends TestCase
{
    use ConsecutiveAssertTrait;

    public function testFetchTagsSavesToCache(): void
    {
        $resp  = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $http  = $this->mockRequest(
            'https://api.github.com/repos/phpcq/repository-builder/git/matching-refs/tags/',
            'token',
            $resp
        );
        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $item  = $this->getMockBuilder(ItemInterface::class)->getMock();
        // Ensure result is expunged after some time.
        $item->expects($this->once())->method('expiresAfter');

        $resp->expects($this->once())->method('getContent')->willReturn('{"success": true}');

        $cache->expects($this->once())->method('get')->willReturnCallback($this->cacheCallback($item, true));

        $client = new GithubClient($http, $cache, 'token');

        $client->fetchTags('phpcq/repository-builder');
    }

    public function testFetchTagsDoesNotSaveExceptionToCacheButThrowsIt(): void
    {
        $resp  = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $resp
            ->expects($this->exactly(3))
            ->method('getInfo')
            ->will(
                $this->handleConsecutive(
                    ['arguments' => ['http_code'], 'return' => 400],
                    ['arguments' => ['url'], 'return' => 'url'],
                    ['arguments' => ['response_headers'], 'return' => []],
                )
            );
        $resp->expects($this->atLeastOnce())->method('getContent')->willReturn('{"success": false}');

        $exc   = new ClientException($resp);
        $http  = $this->mockRequest(
            'https://api.github.com/repos/phpcq/repository-builder/git/matching-refs/tags/',
            'token',
            $exc
        );
        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $item  = $this->getMockBuilder(ItemInterface::class)->getMock();
        // Ensure result is not cached.
        $item->expects($this->never())->method('expiresAfter');

        $cache->expects($this->once())->method('get')->willReturnCallback($this->cacheCallback($item, false));

        $client = new GithubClient($http, $cache, 'token');

        $this->expectException(DataNotAvailableException::class);
        $this->expectExceptionMessage('{"success": false}');

        $client->fetchTags('phpcq/repository-builder');
    }

    public function testFetchTagSavesToCache(): void
    {
        $resp  = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $http  = $this->mockRequest(
            'https://api.github.com/repos/phpcq/repository-builder/releases/tags/1.0',
            'token',
            $resp
        );
        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $item  = $this->getMockBuilder(ItemInterface::class)->getMock();
        // Ensure result is expunged after some time.
        $item->expects($this->once())->method('expiresAfter');

        $resp->expects($this->once())->method('getContent')->willReturn('{"success": true}');

        $cache->expects($this->once())->method('get')->willReturnCallback($this->cacheCallback($item, true));

        $client = new GithubClient($http, $cache, 'token');

        $client->fetchTag('phpcq/repository-builder', '1.0');
    }

    public function testFetchTagDoesNotSaveExceptionToCacheButThrowsIt(): void
    {
        $resp  = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $resp
            ->expects($this->exactly(3))
            ->method('getInfo')
            ->will(
                $this->handleConsecutive(
                    ['arguments' => ['http_code'], 'return' => 400],
                    ['arguments' => ['url'], 'return' => 'url'],
                    ['arguments' => ['response_headers'], 'return' => []],
                )
            );
        $exc   = new ClientException($resp);

        $http  = $this->mockRequest(
            'https://api.github.com/repos/phpcq/repository-builder/releases/tags/1.0',
            'token',
            $exc
        );
        $resp->expects($this->atLeastOnce())->method('getContent')->willReturn('{"success": false}');
        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $item  = $this->getMockBuilder(ItemInterface::class)->getMock();
        // Ensure result is not cached.
        $item->expects($this->never())->method('expiresAfter');

        $cache->expects($this->once())->method('get')->willReturnCallback($this->cacheCallback($item, false));

        $client = new GithubClient($http, $cache, 'token');

        $this->expectException(DataNotAvailableException::class);
        $this->expectExceptionMessage('{"success": false}');

        $client->fetchTag('phpcq/repository-builder', '1.0');
    }

    public function testFetchFileSavesToCache(): void
    {
        $resp  = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $http  = $this->mockRequest(
            'https://raw.githubusercontent.com/phpcq/repository-builder/1.0/some/file',
            'token',
            $resp
        );
        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $item  = $this->getMockBuilder(ItemInterface::class)->getMock();

        // Ensure result is expunged after some time.
        $item->expects($this->once())->method('expiresAfter');

        $resp->expects($this->once())->method('getContent')->willReturn('{"success": true}');

        $cache
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(
                function (string $cacheKey, Closure $callback) use ($item) {
                    $this->assertStringNotContainsString('/', $cacheKey);
                    $this->assertStringNotContainsString(':', $cacheKey);
                    $save = false;
                    $result = $callback->__invoke($item, $save);
                    $this->assertTrue($save);

                    return $result;
                }
            );

        $client = new GithubClient($http, $cache, 'token');

        $client->fetchFile('phpcq/repository-builder', '1.0', 'some/file');
    }

    public function testFetchFileSavesExceptionToCacheAndThrowsIt(): void
    {
        $resp  = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $resp
            ->expects($this->exactly(3))
            ->method('getInfo')
            ->will(
                $this->handleConsecutive(
                    ['arguments' => ['http_code'], 'return' => 400],
                    ['arguments' => ['url'], 'return' => 'url'],
                    ['arguments' => ['response_headers'], 'return' => []],
                )
            );
        $exc   = new ClientException($resp);
        $resp->expects($this->atLeastOnce())->method('getContent')->willReturn('{"success": false}');

        $http  = $this->mockRequest(
            'https://raw.githubusercontent.com/phpcq/repository-builder/1.0/some/file',
            'token',
            $exc
        );
        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $item  = $this->getMockBuilder(ItemInterface::class)->getMock();

        // Ensure exception is expunged after 3600 seconds.
        $item->expects($this->once())->method('expiresAfter')->with(3600);

        $invocations = 0;
        $cache
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(
                function (string $cacheKey, Closure $callback) use ($item, &$invocations) {
                    $this->assertStringNotContainsString('/', $cacheKey);
                    $this->assertStringNotContainsString(':', $cacheKey);
                    $save = false;
                    $result = $callback->__invoke($item, $save);
                    if (++$invocations === 1) {
                        $this->assertFalse($save);
                    } else {
                        $this->assertTrue($save);
                    }

                    return $result;
                }
            );

        $client = new GithubClient($http, $cache, 'token');

        $this->expectException(DataNotAvailableException::class);
        $this->expectExceptionMessage('{"success": false}');

        $client->fetchFile('phpcq/repository-builder', '1.0', 'some/file');
    }

    public function cacheCallback(ItemInterface $item, bool $shouldSave)
    {
        return function (string $cacheKey, Closure $callback) use ($item, $shouldSave) {
            $this->assertStringNotContainsString('/', $cacheKey);
            $this->assertStringNotContainsString(':', $cacheKey);
            $save       = false;
            $result = $callback->__invoke($item, $save);
            $this->assertSame($shouldSave, $save);

            return $result;
        };
    }

    private function mockRequest(
        string $uri,
        string $token,
        ResponseInterface|ClientException $result
    ): HttpClientInterface&MockObject {
        $http = $this->getMockBuilder(HttpClientInterface::class)->getMock();

        $invocation = $http
            ->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                $uri,
                ['headers' => ['Authorization' => 'token ' . $token]]
            );
        if ($result instanceof ClientException) {
            $invocation->willThrowException($result);
        } else {
            $invocation->willReturn($result);
        }

        return $http;
    }
}
