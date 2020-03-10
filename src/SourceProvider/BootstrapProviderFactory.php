<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\SourceProvider;

use Phpcq\RepositoryBuilder\Util\StringUtil;

class BootstrapProviderFactory implements SourceRepositoryFactoryInterface
{
    public function create(array $configuration): SourceRepositoryInterface
    {
        $sourcePath = StringUtil::makeAbsolutePath($configuration['source_dir'], getcwd());

        return new BootstrapProviderRepository($sourcePath);
    }
}
