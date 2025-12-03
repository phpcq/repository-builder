<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\Util;

use Phpcq\RepositoryBuilder\Util\StringUtil;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(StringUtil::class)]
class StringUtilTest extends TestCase
{
    public static function makeFilenameProvider(): array
    {
        return [
            [
                'expected' => '',
                'value'    => '',
            ],
            [
                'expected' => 'http---www.example.org-foo-bar.baz',
                'value'    => 'http://www.example.org/foo/bar.baz',
            ],
        ];
    }

    #[DataProvider('makeFilenameProvider')]
    public function testMakeFilename(string $expected, string $value): void
    {
        $this->assertSame($expected, StringUtil::makeFilename($value));
    }
}
