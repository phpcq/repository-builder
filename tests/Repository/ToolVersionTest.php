<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\Repository;

use Phpcq\RepositoryBuilder\Repository\InlineBootstrap;
use Phpcq\RepositoryBuilder\Repository\ToolHash;
use Phpcq\RepositoryBuilder\Repository\ToolVersion;
use Phpcq\RepositoryBuilder\Repository\VersionRequirement;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\RepositoryBuilder\Repository\ToolVersion
 */
class ToolVersionTest extends TestCase
{
    public function testInitializesWithEmptyValues(): void
    {
        $version = new ToolVersion('supertool', '1.0.0', null, null, null, null, null);
        $this->assertSame('supertool', $version->getName());
        $this->assertSame('1.0.0', $version->getVersion());
        $this->assertSame(null, $version->getPharUrl());
        $this->assertSame([], iterator_to_array($version->getRequirements()->getIterator()));
        $this->assertSame(null, $version->getHash());
        $this->assertSame(null, $version->getSignatureUrl());
    }

    public function testInitializesWithAllValues(): void
    {
        $version = new ToolVersion(
            'supertool',
            '1.0.0',
            'https://example.org/supertool.phar',
            [$requirement1 = new VersionRequirement('test1')],
            $hash = new ToolHash(ToolHash::SHA_512, 'abcdefgh'),
            'https://example.org/supertool.phar.sig',
            $bootstrap = new InlineBootstrap('1.0.0', 'bootstrap 1')
        );
        $this->assertSame('supertool', $version->getName());
        $this->assertSame('1.0.0', $version->getVersion());
        $this->assertSame('https://example.org/supertool.phar', $version->getPharUrl());
        $this->assertSame([$requirement1], iterator_to_array($version->getRequirements()->getIterator()));
        $this->assertSame($hash, $version->getHash());
        $this->assertSame('https://example.org/supertool.phar.sig', $version->getSignatureUrl());
        $this->assertSame($bootstrap, $version->getBootstrap());
    }

    public function setterProvider(): array
    {
        return [
            ['PharUrl', 'https://example.org/supertool.phar'],
            ['Hash', new ToolHash(ToolHash::SHA_512, 'abcdefgh')],
            ['SignatureUrl', 'https://example.org/supertool.phar.sig'],
            ['Bootstrap', new InlineBootstrap('1.0.0', 'bootstrap 1')],
        ];
    }

    /**
     * @dataProvider setterProvider
     */
    public function testSetterWorks(string $propertyName, $value): void
    {
        $version = new ToolVersion('supertool', '1.0.0', null, null, null, null, null);
        $this->assertSame($version, call_user_func([$version, 'set' . $propertyName], $value));
        $this->assertSame($value, call_user_func([$version, 'get' . $propertyName]));
    }
}
