<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Api;

use Phpcq\RepositoryBuilder\Exception\DataNotAvailableException;
use Phpcq\RepositoryBuilder\Util\StringUtil;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GithubClient implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private HttpClientInterface $httpClient;

    private CacheInterface $cache;

    private string $token;

    public function __construct(HttpClientInterface $httpClient, CacheInterface $cache, string $token)
    {
        $this->httpClient = $httpClient;
        $this->cache      = $cache;
        $this->token      = $token;
        $this->logger     = new NullLogger();
    }

    public function fetchTags(string $repository): array
    {
        return $this->fetchJson(
            'https://api.github.com/repos/' . $repository . '/git/matching-refs/tags/'
        );
    }

    public function fetchTag(string $repository, string $tagName): array
    {
        // We handle exceptions differently here:
        // - we cache successful responses forever
        // - but exceptions only for an hour.
        return $this->fetchJson(
            'https://api.github.com/repos/' . $repository . '/releases/tags/' . $tagName
        );
    }

    public function fetchFile(string $repository, string $refSpec, string $filePath): array
    {
        // We handle exceptions differently here - we cache file downloads forever but exceptions only for an hour.
        return $this->cache->get(
            StringUtil::makeFilename('file_' . $repository . '/' . $refSpec . '/' . $filePath),
            function (ItemInterface $item, bool &$save) use ($repository, $refSpec, $filePath) {
                $save = true;
                try {
                    return $this->fetchJson(
                        'https://raw.githubusercontent.com/' . $repository . '/' . $refSpec . '/' . $filePath
                    );
                } catch (DataNotAvailableException $exception) {
                    $item->expiresAfter(3600);
                    return $exception;
                }
            }
        );
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    private function fetchJson(string $url): array
    {
        $value    = $this->cache->get(
            StringUtil::makeFilename($url),
            function (ItemInterface $item, bool &$save) use ($url) {
                try {
                    $data = $this->fetchHttp($url);
                    $save = true;
                    // Cache up to two days.
                    $item->expiresAfter(rand(86400, 2 * 86400));
                    return $data;
                } catch (DataNotAvailableException $exception) {
                    return $exception;
                }
            }
        );
        if ($value instanceof DataNotAvailableException) {
            throw $value;
        }

        return $value;
    }

    private function fetchHttp(string $url): array
    {
        $this->logger->debug('Fetching: ' . $url);
        try {
            return json_decode(
                $this->httpClient->request(
                    'GET',
                    $url,
                    ['headers' => ['Authorization' => 'token ' . $this->token]]
                )->getContent(),
                true
            );
        } catch (ClientException $exception) {
            throw new DataNotAvailableException(
                $exception->getResponse()->getContent(false),
                (int) $exception->getCode(),
                $exception
            );
        }
    }
}
