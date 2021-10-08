<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\SourceProvider\Plugin\Github;

use Phpcq\RepositoryBuilder\Api\GithubClient;
use Phpcq\RepositoryBuilder\Exception\DataNotAvailableException;
use Phpcq\RepositoryBuilder\SourceProvider\Plugin\Github\JsonLoader;
use Phpcq\RepositoryBuilder\SourceProvider\Plugin\Github\Repository;
use Phpcq\RepositoryDefinition\Plugin\PhpFilePluginVersion;
use Phpcq\RepositoryDefinition\VersionRequirementList;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/** @covers \Phpcq\RepositoryBuilder\SourceProvider\Plugin\Github\Repository */
class RepositoryTest extends TestCase
{
    /** @SuppressWarnings(PHPMD.ExcessiveMethodLength) */
    public function testFunctionality(): void
    {
        $tags = [
            ['ref' => 'refs/tags/1.0.0'],
            ['ref' => 'refs/tags/invalid-tag-that-will-get-filtered-out'],
            ['ref' => 'refs/tags/2.0.0'],
        ];
        $json100 = [
            'name' => 'dummy',
            'type' => 'php-file',
            'url' => 'src/file100.php',
            'api-version' => '1.0.0',
            'requirements' => [
                'php' => [
                    'ext-json' => '*'
                ],
                'composer' => [
                    'vendor2/plugin1' => '*'
                ],
                'tool' => [
                    'tool-name' => ['constraints' => '*']
                ],
                'plugin' => [
                    'plugin-name' => ['constraints' => '*']
                ],
            ],
        ];
        $json200 = [
            'name' => 'dummy',
            'type' => 'php-file',
            'url' => 'src/file200.php',
            'api-version' => '1.0.0',
            'requirements' => [],
        ];

        $client = $this->getMockBuilder(HttpClientInterface::class)->disableOriginalConstructor()->getMock();
        $github = $this->getMockBuilder(GithubClient::class)->disableOriginalConstructor()->getMock();
        $loader = new JsonLoader($github, 'vendor1/plugin1');
        $github
            ->expects(self::once())
            ->method('fetchTags')
            ->with('vendor1/plugin1')
            ->willReturn($tags);
        $github
            ->expects(self::exactly(2))
            ->method('fetchFile')
            ->withConsecutive(
                ['vendor1/plugin1', '1.0.0', 'phpcq-plugin.json'],
                ['vendor1/plugin1', '2.0.0', 'phpcq-plugin.json']
            )
            ->willReturnOnConsecutiveCalls($json100, $json200);
        $github
            ->method('fileUri')
            ->willReturnCallback(function (string $repository, string $refSpec, string $filePath): string {
                return sprintf(
                    '%1$s/fixtures/github-plugin/%2$s/%3$s/%4$s',
                    dirname(__DIR__, 3),
                    $repository,
                    $refSpec,
                    $filePath
                );
            });

        $repository = new Repository($loader, $client);

        /** @var list<PhpFilePluginVersion> $versions */
        $versions = iterator_to_array($repository->getPluginIterator());

        self::assertCount(2, $versions);

        self::assertInstanceOf(PhpFilePluginVersion::class, $versions[0]);
        self::assertSame('dummy', $versions[0]->getName());
        self::assertSame('1.0.0.0', $versions[0]->getVersion());
        self::assertSame('1.0.0', $versions[0]->getApiVersion());
        self::assertSame(
            dirname(__DIR__, 3) . '/fixtures/github-plugin/vendor1/plugin1/1.0.0/src/file100.php',
            $versions[0]->getFilePath()
        );

        $hash = $versions[0]->getHash();
        self::assertSame('sha-512', $hash->getType());
        self::assertSame(
            '0de70c797aa39a6a3f09694e01cc94d6' .
            'cdd6dcb960c5be6eb0e1f89064e368ae' .
            'd3e9a090ce6cec7e5ac22c2024f26354' .
            'ebe37d667c0857a1fafb17e11e68f98f',
            $hash->getValue()
        );
        self::assertNull($versions[0]->getSignaturePath());
        self::assertVersionRequirements(
            [['name' => 'vendor2/plugin1', 'constraint' => '*']],
            $versions[0]->getRequirements()->getComposerRequirements()
        );
        self::assertVersionRequirements(
            [['name' => 'ext-json', 'constraint' => '*']],
            $versions[0]->getRequirements()->getPhpRequirements()
        );
        self::assertVersionRequirements(
            [['name' => 'plugin-name', 'constraint' => '*']],
            $versions[0]->getRequirements()->getPluginRequirements()
        );
        self::assertVersionRequirements(
            [['name' => 'tool-name', 'constraint' => '*']],
            $versions[0]->getRequirements()->getToolRequirements()
        );

        self::assertInstanceOf(PhpFilePluginVersion::class, $versions[1]);
        self::assertSame('dummy', $versions[1]->getName());
        self::assertSame('2.0.0.0', $versions[1]->getVersion());
        self::assertSame('1.0.0', $versions[1]->getApiVersion());
        self::assertSame(
            dirname(__DIR__, 3) . '/fixtures/github-plugin/vendor1/plugin1/2.0.0/src/file200.php',
            $versions[1]->getFilePath()
        );

        $hash = $versions[1]->getHash();
        self::assertSame('sha-512', $hash->getType());
        self::assertSame(
            '1651fd49d8e1d24fe7f1c25c76c40f4d' .
            '37ff79d2abaeb5752fc33725b680ceea' .
            'db97a7e99ea5eb142831c869aa665682' .
            'c0a14db7f369def137c7940fd49dbd1f',
            $hash->getValue()
        );
        self::assertNull($versions[1]->getSignaturePath());
        self::assertVersionRequirements([], $versions[1]->getRequirements()->getComposerRequirements());
        self::assertVersionRequirements([], $versions[1]->getRequirements()->getPhpRequirements());
        self::assertVersionRequirements([], $versions[1]->getRequirements()->getPluginRequirements());
        self::assertVersionRequirements([], $versions[1]->getRequirements()->getToolRequirements());
    }

    public function testIgnoresTagsWithoutJson(): void
    {
        $client = $this->getMockBuilder(HttpClientInterface::class)->disableOriginalConstructor()->getMock();
        $github = $this->getMockBuilder(GithubClient::class)->disableOriginalConstructor()->getMock();
        $loader = new JsonLoader($github, 'vendor1/plugin1');

        $github
            ->expects(self::once())
            ->method('fetchTags')
            ->with('vendor1/plugin1')
            ->willReturn([['ref' => 'refs/tags/1.0.0']]);
        $github
            ->expects(self::once())
            ->method('fetchFile')
            ->with('vendor1/plugin1', '1.0.0', 'phpcq-plugin.json')
            ->willThrowException(new DataNotAvailableException());

        $repository = new Repository($loader, $client);

        /** @var list<PhpFilePluginVersion> $versions */
        $versions = iterator_to_array($repository->getPluginIterator());

        self::assertCount(0, $versions);
    }

    public static function assertVersionRequirements(array $expected, VersionRequirementList $list): void
    {
        $actual = [];
        foreach ($list as $item) {
            $actual[] = [
                'name'       => $item->getName(),
                'constraint' => $item->getConstraint(),
            ];
        }

        self::assertSame($expected, $actual);
    }
}
