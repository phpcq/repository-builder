<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\Repository\Tool;

use Phpcq\RepositoryBuilder\Repository\Tool\ToolHash;
use Phpcq\RepositoryBuilder\Repository\Tool\ToolRequirements;
use Phpcq\RepositoryBuilder\Repository\Tool\ToolVersion;
use Phpcq\RepositoryBuilder\Repository\VersionRequirement;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\RepositoryBuilder\Repository\Tool\ToolVersion
 */
class ToolVersionTest extends TestCase
{
    public function testInitializesWithEmptyValues(): void
    {
        $version = new ToolVersion('supertool', '1.0.0', null, null, null, null);
        $this->assertSame('supertool', $version->getName());
        $this->assertSame('1.0.0', $version->getVersion());
        $this->assertSame(null, $version->getPharUrl());
        $this->assertSame([], iterator_to_array($version->getRequirements()->getPhpRequirements()->getIterator()));
        $this->assertSame([], iterator_to_array($version->getRequirements()->getComposerRequirements()->getIterator()));
        $this->assertSame(null, $version->getHash());
        $this->assertSame(null, $version->getSignatureUrl());
    }

    public function testInitializesWithAllValues(): void
    {
        $requirements = new ToolRequirements();
        $requirements->getPhpRequirements()->add($requirement1 = new VersionRequirement('test1'));

        $version = new ToolVersion(
            'supertool',
            '1.0.0',
            'https://example.org/supertool.phar',
            $requirements,
            $hash = ToolHash::create(ToolHash::SHA_512, 'abcdefgh'),
            'https://example.org/supertool.phar.sig',
        );
        $this->assertSame('supertool', $version->getName());
        $this->assertSame('1.0.0', $version->getVersion());
        $this->assertSame('https://example.org/supertool.phar', $version->getPharUrl());
        $this->assertSame($requirements, $version->getRequirements());
        $this->assertSame($hash, $version->getHash());
        $this->assertSame('https://example.org/supertool.phar.sig', $version->getSignatureUrl());
    }

    public function setterProvider(): array
    {
        return [
            ['PharUrl', 'https://example.org/supertool.phar'],
            ['Hash', ToolHash::create(ToolHash::SHA_512, 'abcdefgh')],
            ['SignatureUrl', 'https://example.org/supertool.phar.sig'],
        ];
    }

    /**
     * @dataProvider setterProvider
     */
    public function testSetterWorks(string $propertyName, $value): void
    {
        $version = new ToolVersion('supertool', '1.0.0', null, null, null, null);
        $this->assertSame($version, call_user_func([$version, 'set' . $propertyName], $value));
        $this->assertSame($value, call_user_func([$version, 'get' . $propertyName]));
    }
}
