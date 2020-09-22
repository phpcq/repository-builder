<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Command;

use InvalidArgumentException;
use Phpcq\RepositoryBuilder\JsonRepositoryWriter;
use Phpcq\RepositoryBuilder\RepositoryBuilder;
use Phpcq\RepositoryBuilder\RepositoryDiffBuilder;
use Phpcq\RepositoryBuilder\SourceProvider\SourceRepositoryFactoryInterface;
use Phpcq\RepositoryBuilder\SourceProvider\SourceRepositoryInterface;
use Phpcq\RepositoryBuilder\SourceProvider\ToolVersionFilter;
use Phpcq\RepositoryBuilder\SourceProvider\ToolVersionFilterRegistry;
use Psr\Log\LoggerAwareInterface;
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
 *   allowed_versions: TRepositoryBuilderConfigurationAllowedVersions,
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

        $configFile = realpath($this->getConfigOptionString($input, 'config'));
        if (false === $configFile || !is_readable($configFile)) {
            throw new InvalidArgumentException(
                'Config file not found: ' . $this->getConfigOptionString($input, 'config')
            );
        }

        $outdir = $this->getConfigOptionString($input, 'output-directory');
        if (!is_dir($outdir) && !mkdir($outdir, 0775, true)) {
            throw new InvalidArgumentException('Could not create directory: ' . $outdir);
        }
        $outdir = realpath($outdir);

        chdir(dirname($configFile));

        /** @psalm-var TRepositoryBuilderConfiguration $config */
        $config = Yaml::parse(file_get_contents($configFile));

        $filterRegistry = $this->loadFilterRegistry($config['allowed_versions'] ?? []);

        $providers = $this->loadProviders(
            $config['repositories'] ?? [],
            $filterRegistry
        );

        $diff = null;
        if ($output->isVerbose()) {
            $diff = new RepositoryDiffBuilder($outdir);
        }
        $writer = new JsonRepositoryWriter($outdir);
        $builder = new RepositoryBuilder($providers, $writer);

        $builder->build();

        if (isset($diff) && null !== ($generated = $diff->generate())) {
            $buffer = $generated->asString('');
            if (null !== ($truncate = $input->getOption('truncate'))) {
                $buffer = substr($buffer, 0, (int) $truncate - 3) . '...';
            }
            $output->writeln($buffer);
        }

        return 0;
    }

    private function getConfigOptionString(InputInterface $input, string $option): string
    {
        /** @psalm-suppress PossiblyInvalidCast - we don't have array options */
        return (string) $input->getOption($option);
    }

    /** @psalm-param TRepositoryBuilderConfigurationAllowedVersions $allowedVersions */
    private function loadFilterRegistry(array $allowedVersions): ToolVersionFilterRegistry
    {
        $filters = [];
        foreach ($allowedVersions as $toolName => $constraint) {
            $filters[] = new ToolVersionFilter($toolName, $constraint);
        }

        return new ToolVersionFilterRegistry($filters);
    }

    /**
     * Returns the providers.
     *
     * @psalm-param TRepositoryBuilderConfigurationRepositoryConfigurationArray $repositoryConfig
     *
     * @return SourceRepositoryInterface[]
     * @psalm-return list<SourceRepositoryInterface>
     */
    private function loadProviders(array $repositoryConfig, ToolVersionFilterRegistry $filterRegistry): array
    {
        return array_map(function ($repository) use ($filterRegistry) {
            if (!$this->repositoryFactories->has($repository['type'])) {
                throw new InvalidArgumentException('Unknown repository type: ' . $repository['type']);
            }

            /** @var SourceRepositoryFactoryInterface $factory */
            $factory     = $this->repositoryFactories->get($repository['type']);
            $source      = $factory->create($repository, $filterRegistry);
            if ($source instanceof LoggerAwareInterface) {
                $source->setLogger($this->logger);
            }
            return $source;
        }, array_values($repositoryConfig));
    }
}
