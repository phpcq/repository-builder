<?php

use Phpcq\Config\BuildConfigInterface;
use Phpcq\Plugin\ConfigurationPluginInterface;

/**
 * Tool home: https://github.com/phpmd/phpmd
 */
return new class implements ConfigurationPluginInterface {
    public function getName() : string
    {
        return 'phpmd';
    }

    /**
     * format          [string] Output format to use (ansi, html, json, text, xml).
     * ruleset         [array]  List of rulesets (cleancode, codesize, controversial, design, naming, unusedcode).
     *
     * custom_flags    [string] Any custom flags to pass to phploc. For valid flags refer to the phploc documentation.
     *
     * directories     [array]  source directories to be analyzed with phploc.
     *
     * @var string[]
     */
    private static $knownConfigKeys = [
        'format'          => 'format',
        'ruleset'         => 'ruleset',
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
        [$should, $excluded] = $this->processDirectories($config['directories']);

        $flags = [
            'format' => 'text',
            'ruleset' => 'naming,unusedcode',
        ];

        foreach ($flags as $key => $value) {
            if ('' !== ($value = $this->commaValues($config, $key))) {
                $flags[$key] = $value;
            }
        }

        $args = [
            implode(',', $should),
            $flags['format'],
            $flags['ruleset'],
        ];

        if ([] !== $excluded) {
            $exclude = [];
            foreach ($excluded as $path) {
                if ('' === ($path = trim($path))) {
                    continue;
                }
                $exclude[] = $path;
            }
            $args[] = '--exclude=' . implode(',', $exclude);
        }
        if ('' !== ($values = $config['custom_flags'] ?? '')) {
            $args[] = $values;
        }

        yield $buildConfig
            ->getTaskFactory()
            ->buildRunPhar('phpmd', $args)
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
