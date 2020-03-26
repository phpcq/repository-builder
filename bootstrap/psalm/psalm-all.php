<?php

use Phpcq\Config\BuildConfigInterface;
use Phpcq\Plugin\Config\ConfigOptionsBuilderInterface;
use Phpcq\Plugin\ConfigurationPluginInterface;

return new class implements ConfigurationPluginInterface {
    public function getName() : string
    {
        return 'psalm';
    }

    public function describeOptions(ConfigOptionsBuilderInterface $configOptionsBuilder) : void
    {
        $configOptionsBuilder
            ->describeBoolOption('debug', 'Show debug information.')
            ->describeBoolOption('debug_by_line', 'Debug information on a line-by-line level')
            ->describeBoolOption('shepherd', 'Send data to Shepherd, Psalm\'s GitHub integration tool.')
            ->describeStringOption('shepherd_host', 'Override shepherd host');

        $configOptionsBuilder->describeStringOption(
            'custom_flags',
            'Any custom flags to pass to phpunit. For valid flags refer to the phpunit documentation.',
        );
    }

    public function processConfig(array $config, BuildConfigInterface $buildConfig) : iterable
    {
        yield $buildConfig
            ->getTaskFactory()
            ->buildRunPhar('psalm', $this->buildArguments($config))
            ->withWorkingDirectory($buildConfig->getProjectConfiguration()->getProjectRootPath())
            ->build();
    }

    private function buildArguments(array $config) : array
    {
        $arguments = [];

        foreach (['debug', 'debug_by_line'] as $flag) {
            if (isset($config[$flag])) {
                $arguments[] = '--' .  str_replace('_', '-', $flag);
            }
        }

        if (isset($config['shepherd'])) {
            if (isset($config['shepherd_host'])) {
                $arguments[] = '--shepherd=' . $config['shepherd_host'];
            } else {
                $arguments[] = '--shepherd';
            }
        }

        return $arguments;
    }
};
