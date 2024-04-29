<?php

declare(strict_types=1);

use Phpcq\RepositoryBuilder\Api\GithubClient;
use Phpcq\RepositoryBuilder\Command\RebuildCommand;
use Phpcq\RepositoryBuilder\SourceProvider\Plugin\Github\RepositoryFactory as GithubRepositoryFactory;
use Phpcq\RepositoryBuilder\SourceProvider\PluginProviderRepositoryFactory;
use Phpcq\RepositoryBuilder\SourceProvider\Tool\Github\TagProviderRepositoryFactory;
use Phpcq\RepositoryBuilder\SourceProvider\Tool\PharIo\RepositoryFactory as PharIoRepositoryFactory;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpClient\CachingHttpClient;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpKernel\HttpCache\Store;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_locator;

return function(ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services->set('http.store')
        ->class(Store::class)
        ->args(['%kernel.project_dir%/var/http-cache']);

    $services->set('http.client.internal')
        ->class(HttpClientInterface::class)
        ->factory([HttpClient::class, 'create']);

    $services->set('http.client')
        ->class(CachingHttpClient::class)
        ->args([service('http.client.internal'), service('http.store')]);

    $services->set(GithubClient::class)
        ->args([service('http.client'), service('github.cache'), param('env(GITHUB_TOKEN)')]);

    $services->set('github.cache')
        ->class(PhpFilesAdapter::class)
        ->arg('$namespace', 'github-')
        ->arg('$directory', '%kernel.project_dir%/var/github-cache');

    $services->set(PharIoRepositoryFactory::class)
        ->args([service('http.client'), '%kernel.project_dir%/var/repositories/phar.io'])
        ->tag('repository.factory', ['key' => 'tool-phar.io']);

    $services->set(TagProviderRepositoryFactory::class)
        ->args([service(GithubClient::class)])
        ->tag('repository.factory', ['key' => 'tool-github']);

    /** @psalm-suppress DeprecatedClass */
    $services->set(PluginProviderRepositoryFactory::class)
        ->tag('repository.factory', ['key' => 'plugin']);

    $services->set(GithubRepositoryFactory::class)
        ->tag('repository.factory', ['key' => 'plugin-github']);

    $services->load('Phpcq\\RepositoryBuilder\\Command\\', '../src/Command')
        ->tag('console.command');

    $services->set(RebuildCommand::class)
        ->args([tagged_locator(tag: 'repository.factory', indexAttribute: 'key')])
        ->tag('console.command');
};
