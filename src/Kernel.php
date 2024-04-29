<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

use function dirname;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function getProjectDir(): string
    {
        return dirname(__DIR__);
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->parameters()->set('container.dumper.inline_class_loader', true);
        $container->import('../config/{packages}/*.php');
        $container->import('../config/{services}.php');
    }
}
