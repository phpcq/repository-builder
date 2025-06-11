<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Command;

use InvalidArgumentException;
use Phpcq\RepositoryBuilder\JsonRepositoryWriter;
use Phpcq\RepositoryBuilder\RepositoryBuilder;
use Phpcq\RepositoryBuilder\RepositoryDiffBuilder;
use Phpcq\RepositoryBuilder\SourceProvider\CompoundRepository;
use Phpcq\RepositoryBuilder\SourceProvider\LoaderContext;
use Phpcq\RepositoryBuilder\SourceProvider\RepositoryLoader;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Yaml\Yaml;

use function substr;

/**
 * This rebuilds the repository.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 *
 * @psalm-type TRepositoryBuilderConfigurationAllowedVersions = array<string, string>
 * @psalm-type TRepositoryBuilderConfigurationRepositoryConfiguration = array{
 *     type: string,
 *     source_dir?: string,
 *     repository?: string,
 *     tool_name?: string
 *   }
 * @psalm-type TRepositoryBuilderConfigurationRepositoryConfigurationArray = array<
 *   string,
 *   TRepositoryBuilderConfigurationRepositoryConfiguration
 * >
 * @psalm-type TRepositoryBuilderConfiguration = array{
 *   repositories: TRepositoryBuilderConfigurationRepositoryConfigurationArray
 * }
 */
final class RebuildCommand extends Command
{
    private ServiceLocator $repositoryFactories;

    private LoggerInterface $logger;

    /**
     * Create a new instance.
     *
     * @param ServiceLocator $repositoryFactories
     */
    public function __construct(ServiceLocator $repositoryFactories)
    {
        parent::__construct();
        $this->repositoryFactories = $repositoryFactories;
    }

    protected function configure(): void
    {
        parent::configure();
        $this->setName('phpcq:rebuild');
        $this->addOption(
            'output-directory',
            'o',
            InputOption::VALUE_REQUIRED,
            'The desired output directory',
            './web'
        );
        $this->addOption(
            'config',
            'c',
            InputOption::VALUE_REQUIRED,
            'Input configuration',
            'sources.yaml'
        );
        $this->addOption(
            'truncate',
            't',
            InputOption::VALUE_REQUIRED,
            'Truncate output (Defaults to MAX_ARG_STRLEN)'
        );
    }

    /**
     * @psalm-suppress PossiblyInvalidCast
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger = new ConsoleLogger($output);
        $configFile   = $this->getConfigFile($input);
        $outdir       = $this->getOutputDirectory($input);

        chdir(dirname($configFile));

        /** @psalm-var TRepositoryBuilderConfiguration $config */
        $config = Yaml::parse((string) file_get_contents($configFile));

        $providers = $this->loadProviders($config['repositories'] ?? []);

        $diff = null;
        if ($output->isVerbose()) {
            $diff = new RepositoryDiffBuilder($outdir);
        }
        $writer = new JsonRepositoryWriter($outdir);
        $builder = new RepositoryBuilder($providers, $writer);

        $builder->build();

        if (isset($diff) && null !== ($generated = $diff->generate())) {
            $buffer = $generated->asString('');
            /** @psalm-suppress MixedAssignment */
            if (null !== ($truncate = $input->getOption('truncate'))) {
                $truncate = (int) $truncate;
                if (strlen($buffer) > $truncate) {
                    $buffer = substr($buffer, 0, $truncate) . '...';
                }
            }
            $output->writeln($buffer);
        }

        return 0;
    }

    private function getConfigFile(InputInterface $input): string
    {
        $configFile = realpath($this->getConfigOptionString($input, 'config'));
        if (false === $configFile || !is_readable($configFile)) {
            throw new InvalidArgumentException(
                'Config file not found: ' . $this->getConfigOptionString($input, 'config')
            );
        }

        return $configFile;
    }

    private function getOutputDirectory(InputInterface $input): string
    {
        $outDir = $this->getConfigOptionString($input, 'output-directory');
        if (!is_dir($outDir) && !mkdir($outDir, 0775, true)) {
            throw new InvalidArgumentException('Could not create directory: ' . $outDir);
        }
        $outDir = realpath($outDir);
        assert(is_string($outDir));

        return $outDir;
    }

    private function getConfigOptionString(InputInterface $input, string $option): string
    {
        /** @psalm-suppress PossiblyInvalidCast - we don't have array options */
        return (string) $input->getOption($option);
    }

    /**
     * Returns the providers.
     *
     * @psalm-param TRepositoryBuilderConfigurationRepositoryConfigurationArray $repositoryConfig
     */
    private function loadProviders(array $repositoryConfig): CompoundRepository
    {
        $loader = new RepositoryLoader($this->repositoryFactories);
        $context = LoaderContext::create($loader);

        $repositories = [];
        foreach ($repositoryConfig as $repository) {
            $repositories[] = $loader->load($repository, $context);
        }

        $compound = new CompoundRepository(...$repositories);
        $compound->setLogger($this->logger);

        return $compound;
    }
}
