<?php

use Phpcq\Config\BuildConfigInterface;
use Phpcq\Plugin\Config\ConfigOptionsBuilderInterface;
use Phpcq\Plugin\ConfigurationPluginInterface;

/**
 * Tool home: https://github.com/sebastianbergmann/phpcpd
 */
return new class implements ConfigurationPluginInterface {
    public function getName() : string
    {
        return 'phpcpd';
    }

    public function describeOptions(ConfigOptionsBuilderInterface $configOptionsBuilder) : void
    {
        $configOptionsBuilder->describeArrayOption(
            'names',
            'A list of file names to check.',
            ['*.php']
        );

        $configOptionsBuilder->describeArrayOption(
            'names_exclude',
            'A list of file names to exclude.'
        );

        $configOptionsBuilder->describeArrayOption(
            'regexps_exclude',
            'A list of paths regexps to exclude (example: "#var/.*_tmp#")'
        );

        $configOptionsBuilder->describeStringOption(
            'log',
            'Write result in PMD-CPD XML format to file'
        );

        $configOptionsBuilder->describeIntOption(
            'min_lines',
            'Minimum number of identical lines.',
            5
        );

        $configOptionsBuilder->describeIntOption(
            'min_tokens',
            'Minimum number of identical tokens.',
            70
        );

        $configOptionsBuilder->describeBoolOption(
            'fuzzy',
            'Fuzz variable names',
            false
        );

        $configOptionsBuilder->describeStringOption(
            'custom_flags',
            'Any custom flags to pass to phpcpd. For valid flags refer to the phpcpd documentation.'
        );

        $configOptionsBuilder->describeArrayOption(
            'directories',
            'Source directories to be analyzed with phpcpd.'
        );
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
        if ('' !== ($values = $this->commaValues($config, 'names'))) {
            $args[] = '--names=' . $values;
        }
        if ('' !== ($values = $this->commaValues($config, 'names_exclude'))) {
            $args[] = '--names-exclude=' . $values;
        }
        if ('' !== ($values = $this->commaValues($config, 'regexps_exclude'))) {
            $args[] = '--regexps-exclude=' . $values;
        }
        if ('' !== ($values = $config['log'] ?? '')) {
            $args[] = '--log-pmd=' . $values;
        }
        if ('' !== ($values = $config['min_lines'] ?? '')) {
            $args[] = '--min-lines=' . $values;
        }
        if ('' !== ($values = $config['min_tokens'] ?? '')) {
            $args[] = '--min-tokens=' . $values;
        }
        if ($config['fuzzy'] ?? false) {
            $args[] = '--fuzzy';
        }
        if ('' !== ($values = $config['custom_flags'] ?? '')) {
            $args[] = 'custom_flags';
        }

        yield $buildConfig
            ->getTaskFactory()
            ->buildRunPhar('phpcpd', array_merge($args, $should))
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
