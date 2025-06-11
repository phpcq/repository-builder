<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\SourceProvider;

use Phpcq\RepositoryBuilder\Util\StringUtil;
use RuntimeException;

/**
 * @psalm-type TPluginProviderRepositoryFactoryConfiguration = array{
 *   source_dir: string
 * }
 *
 * @deprecated This is the legacy repository loader.
 *
 * @implements SourceRepositoryFactoryInterface<TPluginProviderRepositoryFactoryConfiguration>
 */
class PluginProviderRepositoryFactory implements SourceRepositoryFactoryInterface
{
    /**
     * @psalm-suppress DeprecatedClass
     */
    public function create(array $configuration, LoaderContext $context): SourceRepositoryInterface
    {
        if (!is_string($sourceDir = $configuration['source_dir'] ?? null)) {
            throw new RuntimeException('No source directory configured');
        }

        $sourcePath = StringUtil::makeAbsolutePath($sourceDir, getcwd());

        return new PluginProviderRepository($sourcePath);
    }
}
