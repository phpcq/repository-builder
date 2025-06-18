<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\SourceProvider;

/** @template T of array */
interface SourceRepositoryFactoryInterface
{
    /** @psalm-param T $configuration */
    public function create(array $configuration, LoaderContext $context): SourceRepositoryInterface;
}
