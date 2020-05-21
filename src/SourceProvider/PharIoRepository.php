<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\SourceProvider;

use DOMElement;
use Generator;
use Phpcq\RepositoryBuilder\File\XmlFile;
use Phpcq\RepositoryBuilder\Repository\ToolHash;
use Phpcq\RepositoryBuilder\Repository\ToolVersion;
use Phpcq\RepositoryBuilder\Util\StringUtil;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * phar.io repository.
 *
 * <repository
 *   xmlns="https://phar.io/repository"
 *   xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
 *   xsi:schemaLocation="https://phar.io/repository https://phar.io/data/repository.xsd">
 *   <phar name="phpunit">
 *     <release version="$toolVersion" url="$urlToPharFile">
 *       <signature type="gpg" url="$urlToSignature"/>
 *       <hash type="$hashType" value="$hashValue"/>
 *     </release>
 *   </phar>
 * </repository>
 */
class PharIoRepository implements VersionProvidingRepositoryInterface
{
    private string $repositoryUrl;

    private string $cacheDir;

    private HttpClientInterface $httpClient;

    public function __construct(string $repositoryUrl, string $cacheDir, HttpClientInterface $httpClient)
    {
        $this->repositoryUrl = $repositoryUrl;
        $this->cacheDir      = $cacheDir;
        $this->httpClient    = $httpClient;
    }

    public function isFresh(): bool
    {
        // TODO: Implement isFresh() method.
        return false;
    }

    public function refresh(): void
    {
        // TODO: Implement refresh() method.
    }

    public function getIterator(): Generator
    {
        foreach ($this->downloadXml()->query('//rootNs:release') as $releaseNode) {
            assert($releaseNode instanceof DOMElement);
            assert($releaseNode->parentNode instanceof DOMElement);
            yield new ToolVersion(
                $releaseNode->parentNode->getAttribute('name'),
                $releaseNode->getAttribute('version'),
                $releaseNode->getAttribute('url'),
                [],
                $this->getHash($releaseNode),
                $this->getSignatureUrl($releaseNode),
                null
            );
        }
    }

    private function downloadXml(): XmlFile
    {
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0775, true);
        }
        $contents = $this->httpClient->request('GET', $this->repositoryUrl);
        $fileName = $this->cacheDir . '/' . StringUtil::makeFilename($this->repositoryUrl);

        file_put_contents($fileName, $contents->getContent());

        return new XmlFile($fileName, 'https://phar.io/repository', 'repository');
    }

    private function getSignatureUrl(DOMElement $releaseNode): string
    {
        /** @var DOMElement $signatureNode */
        $signatureNode = $releaseNode->getElementsByTagName('signature')->item(0);

        if ($signatureNode->hasAttribute('url')) {
            return $signatureNode->getAttribute('url');
        }

        return $releaseNode->getAttribute('url') . '.asc';
    }

    private function getHash(DOMElement $releaseNode): ToolHash
    {
        /** @var DOMElement $hashNode */
        $hashNode  = $releaseNode->getElementsByTagName('hash')->item(0);
        $type      = $hashNode->getAttribute('type');
        $hashValue = $hashNode->getAttribute('value');

        return new ToolHash($type, $hashValue);
    }
}
