<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Test;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;
use PHPUnit\Framework\MockObject\Stub\Stub;

use function array_key_exists;

trait ConsecutiveAssertTrait
{
    /** @param list<array{arguments: list<mixed>, return: mixed}> $arguments */
    public function handleConsecutive(array ...$arguments): Stub
    {
        $invocation = 0;
        $stub = new ReturnCallback(static function () use (&$invocation, $arguments) {
            $isArgs = func_get_args();
            $shouldArgs = $arguments[$invocation]['arguments'];
            Assert::assertSame($shouldArgs, $isArgs);
            if (!array_key_exists('return', $arguments[$invocation])) {
                $invocation++;
                return;
            }
            $return = $arguments[$invocation]['return'];
            $invocation++;
            return $return;
        });

        return $stub;
    }
}
