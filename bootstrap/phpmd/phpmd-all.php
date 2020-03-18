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
     * exclude         [array]  List of excluded files and folders.
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
        'exclude'         => 'exclude',
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
            implode(',', $config['directories']),
            $flags['format'],
            $flags['ruleset'],
        ];

        if ([] !== ($excluded = (array) ($config['exclude'] ?? []))) {
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

    private function commaValues(array $config, string $key): string
    {
        if (!isset($config[$key])) {
            return '';
        }
        return implode(',', (array) $config[$key]);
    }
};
