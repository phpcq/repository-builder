<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Util;

use Symfony\Component\Filesystem\Filesystem;

use function assert;
use function is_string;
use function preg_replace;

class StringUtil
{
    private static ?Filesystem $fileSystem = null;

    public static function makeFilename(string $value): string
    {
        $result = preg_replace('#[^a-zA-Z0-9.]#', '-', $value);
        assert(is_string($result));

        return $result;
    }

    public static function makeAbsolutePath(string $path, string $baseDir): string
    {
        if (null === self::$fileSystem) {
            self::$fileSystem = new Filesystem();
        }

        if (self::$fileSystem->isAbsolutePath($path)) {
            return $path;
        }
         return $baseDir . '/' . $path;
    }
}
