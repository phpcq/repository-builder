<?php

use Phpcq\Config\BuildConfigInterface;
use Phpcq\Plugin\Config\ConfigOptionsBuilderInterface;
use Phpcq\Plugin\ConfigurationPluginInterface;

return new class implements ConfigurationPluginInterface {
    public function getName() : string
    {
        return 'phpunit';
    }

    public function describeOptions(ConfigOptionsBuilderInterface $configOptionsBuilder) : void
    {
        $configOptionsBuilder->describeStringOption(
            'custom_flags',
            'Any custom flags to pass to phpunit. For valid flags refer to the phpunit documentation.',
        );
    }

    public function processConfig(array $config, BuildConfigInterface $buildConfig) : iterable
    {
        yield $buildConfig
            ->getTaskFactory()
            ->buildRunPhar('phpunit')
            ->withWorkingDirectory($buildConfig->getProjectConfiguration()->getProjectRootPath())
            ->build();
    }
};
