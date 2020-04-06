<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Command;

use InvalidArgumentException;
use Phpcq\RepositoryBuilder\JsonRepositoryWriter;
use Phpcq\RepositoryBuilder\RepositoryBuilder;
use Phpcq\RepositoryBuilder\SourceProvider\EnrichingRepositoryInterface;
use Phpcq\RepositoryBuilder\SourceProvider\SourceRepositoryFactoryInterface;
use Phpcq\RepositoryBuilder\SourceProvider\VersionProvidingRepositoryInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Yaml\Yaml;

/**
 * This rebuilds the repository.
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

    protected function configure()
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
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger = new ConsoleLogger($output);

        $configFile = realpath($input->getOption('config'));
        if (!is_readable($configFile)) {
            throw new \InvalidArgumentException('Config file not found: ' . $input->getOption('config'));
        }
        $outdir = realpath($input->getOption('output-directory'));

        chdir(dirname($configFile));

        $config = Yaml::parse(file_get_contents($configFile));
        if (!is_dir($outdir)) {
            mkdir($outdir, 0775, true);
        }

        /** @var VersionProvidingRepositoryInterface[] $versionProviders */
        /** @var EnrichingRepositoryInterface[] $enrichingProviders */
        [$versionProviders, $enrichingProviders] = $this->loadProviders($config['repositories']);

        $writer = new JsonRepositoryWriter($outdir);
        $builder = new RepositoryBuilder($versionProviders, $enrichingProviders, $writer);

        $builder->build();

        return 0;
    }

    /**
     * Returns the version providers and enriching providers.
     *
     * @param array $repositoryConfig
     *
     * @return array|array[]
     */
    private function loadProviders(array $repositoryConfig): array
    {
        $versionProviders   = [];
        $enrichingProviders = [];
        foreach ($repositoryConfig as $repository) {
            if (!$this->repositoryFactories->has($repository['type'])) {
                throw new InvalidArgumentException('Unknown repository type: ' . $repository['type']);
            }
            /** @var SourceRepositoryFactoryInterface $factory */
            $factory = $this->repositoryFactories->get($repository['type']);
            $source  = $factory->create($repository);

            if ($source instanceof VersionProvidingRepositoryInterface) {
                $versionProviders[] = $source;
            }
            if ($source instanceof EnrichingRepositoryInterface) {
                $enrichingProviders[] = $source;
            }
            if ($source instanceof LoggerAwareInterface) {
                $source->setLogger($this->logger);
            }
        }
        return [$versionProviders, $enrichingProviders];
    }
}
