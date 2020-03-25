<?php

use Phpcq\Config\BuildConfigInterface;
use Phpcq\Plugin\Config\ConfigOptionsBuilderInterface;
use Phpcq\Plugin\ConfigurationPluginInterface;

/**
 * Tool home: https://github.com/sebastianbergmann/phploc
 */
return new class implements ConfigurationPluginInterface {
    public function getName() : string
    {
        return 'phploc';
    }

    public function describeOptions(ConfigOptionsBuilderInterface $configOptionsBuilder) : void
    {
        $configOptionsBuilder->describeArrayOption('output', 'List of outputs to use.');

        $configOptionsBuilder->describeStringOption(
            'custom_flags',
            'Any custom flags to pass to phploc. For valid flags refer to the phploc documentation.'
        );

        $configOptionsBuilder->describeArrayOption('directories', 'Source directories to be analyzed with phploc.');
    }

    public function processConfig(array $config, BuildConfigInterface $buildConfig) : iterable
    {
        [$should, $excluded] = $this->processDirectories($config['directories']);
        $args = [];
        if ([] !== $excluded) {
            foreach ($excluded as $path) {
                if ('' === ($path = trim($path))) {
                    continue;
                }
                $args[] = '--exclude=' . $path;

            }
        }
        if ('' !== ($values = $config['custom_flags'] ?? '')) {
            $args[] = 'custom_flags';
        }

        yield $buildConfig
            ->getTaskFactory()
            ->buildRunPhar('phploc', array_merge($args, $should))
            ->withWorkingDirectory($buildConfig->getProjectConfiguration()->getProjectRootPath())
            ->build();
    }

    /**
     * Process the directory list.
     *
     * @param array $directories The directory list.
     *
     * @return array
     */
    private function processDirectories(array $directories): array
    {
        $should  = [];
        $exclude = [];
        foreach ($directories as $directory => $dirConfig) {
            $should[] = $directory;
            if (null !== $dirConfig) {
                if (isset($dirConfig['excluded'])) {
                    foreach ($dirConfig['excluded'] as $excl) {
                        $exclude[] = $directory . '/' . $excl;
                    }
                }
            }
        }
        return [$should, $exclude];
    }

    private function commaValues(array $config, string $key): string
    {
        if (!isset($config[$key])) {
            return '';
        }
        return implode(',', (array) $config[$key]);
    }
};
