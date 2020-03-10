<?php

use Phpcq\Config\BuildConfigInterface;
use Phpcq\Plugin\ConfigurationPluginInterface;

/**
 * Tool home: https://github.com/sebastianbergmann/phploc
 */
return new class implements ConfigurationPluginInterface {
    public function getName() : string
    {
        return 'phploc';
    }

    /**
     * exclude         [array]  List of excluded files and folders.
     * output          [array]  List of outputs to use.
     *
     * custom_flags    [string] Any custom flags to pass to phploc. For valid flags refer to the phploc documentation.
     *
     * directories     [array]  source directories to be analyzed with phploc.
     *
     * @var string[]
     */
    private static $knownConfigKeys = [
        'exclude'         => 'exclude',
        'output'          => 'output',
        'custom_flags'    => 'custom_flags',
        'directories'     => 'directories',
    ];

    public function validateConfig(array $config) : void
    {
        if ($diff = array_diff_key($config, self::$knownConfigKeys)) {
            throw new \Phpcq\Exception\RuntimeException(
                'Unknown config keys encountered: ' . implode(', ', array_keys($diff))
            );
        }
    }

    public function processConfig(array $config, BuildConfigInterface $buildConfig) : iterable
    {
        $args = [];
        if ([] !== ($excluded = (array) ($config['exclude'] ?? []))) {
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
            ->buildRunPhar('phploc', array_merge($args, $config['directories']))
            ->withWorkingDirectory($buildConfig->getProjectConfiguration()->getProjectRootPath())
            ->build();
    }

    private function commaValues(array $config, string $key): string
    {
        if (!isset($config[$key])) {
            return '';
        }
        return implode(',', (array) $config[$key]);
    }
};
