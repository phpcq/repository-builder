<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test\Util;

use Phpcq\RepositoryBuilder\Util\StringUtil;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Phpcq\RepositoryBuilder\Util\StringUtil
 */
class StringUtilTest extends TestCase
{
    public function makeFilenameProvider(): array
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

    /**
     * @dataProvider makeFilenameProvider()
     */
    public function testMakeFilename(string $expected, string $value): void
    {
        $this->assertSame($expected, StringUtil::makeFilename($value));
    }
}
