<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\SourceProvider\Tool\PharIo;

use DOMElement;
use Generator;
use Phpcq\RepositoryBuilder\File\XmlFile;
use Phpcq\RepositoryBuilder\SourceProvider\Tool\ToolVersionProvidingRepositoryInterface;
use Phpcq\RepositoryBuilder\SourceProvider\ToolVersionFilter;
use Phpcq\RepositoryBuilder\Util\StringUtil;
use Phpcq\RepositoryDefinition\Tool\ToolHash;
use Phpcq\RepositoryDefinition\Tool\ToolVersion;
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
class Repository implements ToolVersionProvidingRepositoryInterface
{
    private string $repositoryUrl;

    private string $cacheDir;

    private HttpClientInterface $httpClient;

    private ?ToolVersionFilter $filter;

    public function __construct(
        string $repositoryUrl,
        string $cacheDir,
        HttpClientInterface $httpClient,
        ?ToolVersionFilter $filter
    ) {
        $this->repositoryUrl = $repositoryUrl;
        $this->cacheDir      = $cacheDir;
        $this->httpClient    = $httpClient;
        $this->filter        = $filter;
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

    public function getToolIterator(): Generator
    {
        foreach ($this->downloadXml()->query('//rootNs:release') as $releaseNode) {
            assert($releaseNode instanceof DOMElement);
            assert($releaseNode->parentNode instanceof DOMElement);
            $toolName = $releaseNode->parentNode->getAttribute('name');
            $version  = $releaseNode->getAttribute('version');

            if ($this->filter && ($toolName !== $this->filter->getToolName() || !$this->filter->accepts($version))) {
                continue;
            }

            yield new ToolVersion(
                $toolName,
                $version,
                $releaseNode->getAttribute('url'),
                null,
                $this->getHash($releaseNode),
                $this->getSignatureUrl($releaseNode),
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

        return ToolHash::create($type, $hashValue);
    }
}
