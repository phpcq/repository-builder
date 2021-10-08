<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\SourceProvider;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;

class RepositoryLoader
{
    private ContainerInterface $repositoryFactories;

    public function __construct(ContainerInterface $repositoryFactories)
    {
        $this->repositoryFactories = $repositoryFactories;
    }

    /** @param array<string, mixed> $config */
    public function load(array $config, LoaderContext $context): SourceRepositoryInterface
    {
        if (!is_string($type = $config['type'] ?? null)) {
            throw new InvalidArgumentException(
                'Invalid repository configuration: ' . var_export($config['type'], true)
            );
        }

        if (!$this->repositoryFactories->has($type)) {
            throw new InvalidArgumentException('Unknown repository type: ' . $type);
        }

        /** @var SourceRepositoryFactoryInterface $factory */
        $factory = $this->repositoryFactories->get($type);
        return $factory->create($config, $context);
    }
}
