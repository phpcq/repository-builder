<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Api;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GithubClient implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private HttpClientInterface $httpClient;

    private string $token;

    public function __construct(HttpClientInterface $httpClient, string $token)
    {
        $this->httpClient = $httpClient;
        $this->token      = $token;
    }

    public function fetchJson(string $url): array
    {
        if ($this->logger) {
            $this->logger->debug('Fetching: ' . $url);
        }
        try {
            return json_decode($this->httpClient->request(
                'GET',
                $url,
                ['headers' => ['Authorization' => 'token ' . $this->token]]
            )->getContent(), true);
        } catch (ClientException $exception) {
            if (403 === $exception->getCode()) {
                // Api limit problem?
                var_dump($exception->getResponse()->getContent(false));
            }
            throw $exception;
        }
    }
}