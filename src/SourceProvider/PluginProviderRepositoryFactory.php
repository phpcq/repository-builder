<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\SourceProvider;

use Phpcq\RepositoryBuilder\Util\StringUtil;
use RuntimeException;

use function getcwd;
use function is_string;

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
    public function create(array $configuration, LoaderContext $context): SourceRepositoryInterface
    {
        $sourceDir = $configuration['source_dir'] ?? null;
        if (!is_string($sourceDir)) {
            throw new RuntimeException('No source directory configured');
        }

        $sourcePath = StringUtil::makeAbsolutePath($sourceDir, (string) getcwd());

        return new PluginProviderRepository($sourcePath);
    }
}
