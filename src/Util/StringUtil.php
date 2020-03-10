<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Util;

use Symfony\Component\Filesystem\Filesystem;

class StringUtil
{
    private static ?Filesystem $fileSystem = null;

    public static function makeFilename(string $value): string
    {
        return preg_replace('#[^a-zA-Z0-9.]#', '-', $value);
    }

    public static function makeAbsolutePath(string $path, string $baseDir)
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