<?php

declare(strict_types=1);

namespace App\Command;

use App\Cache\CacheManager;
use App\Config\ConfigManager;
use App\Extract\Extractor;
use App\Update\UpdateManager;
use EMS\CommonBundle\Common\Command\AbstractCommand;
use EMS\CommonBundle\Common\CoreApi\Client;
use EMS\CommonBundle\Common\CoreApi\CoreApi;
use EMS\CommonBundle\Storage\StorageManager;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;
use Symfony\Contracts\Cache\ItemInterface;

class EmsMigrationCommand extends AbstractCommand
{
    private const ARG_CONFIG_FILE_PATH = 'json-path';
    private const ARG_ELASTICMS_URL = 'elasticms-url';
    private const ARG_HASH_ALGO = 'hash-algo';
    private const ARG_USERNAME = 'username';
    private const ARG_PASSWORD = 'password';
    public const OPTION_CACHE_FOLDER = 'cache-folder';
    public const OPTION_FORCE = 'force';
    protected static $defaultName = 'ems:migrate';
    private ConsoleLogger $logger;
    private CoreApi $coreApi;
    private FilesystemAdapter $cache;
    private string $jsonPath;
    private MimeTypeGuesser $mimeTypeGuesser;
    private string $username;
    private StorageManager $storageManager;
    private string $cacheFolder;
    private bool $force;

    protected function configure(): void
    {
        $this
            ->setDescription('Migration web resources to elaticms documents')
            ->addArgument(
                self::ARG_ELASTICMS_URL,
                InputArgument::REQUIRED,
                'Elacticms\'s URL'
            )
            ->addArgument(
                self::ARG_CONFIG_FILE_PATH,
                InputArgument::REQUIRED,
                'Path to an config file (JSON) see documentation'
            )
            ->addOption(
                self::ARG_HASH_ALGO,
                null,
                InputOption::VALUE_OPTIONAL,
                'Algorithm used to hash assets',
                'sha1'
            )
            ->addArgument(self::ARG_USERNAME, InputArgument::OPTIONAL, 'username', null)
            ->addArgument(self::ARG_PASSWORD, InputArgument::OPTIONAL, 'password', null)
            ->addOption(self::OPTION_FORCE, null, InputOption::VALUE_NONE, 'force update all documents')
            ->addOption(self::OPTION_CACHE_FOLDER, null, InputOption::VALUE_OPTIONAL, 'Path to a folder where cache will stored', \implode(DIRECTORY_SEPARATOR, [\sys_get_temp_dir(), 'WebToElasticms']));
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);
        $this->logger = new ConsoleLogger($output);
        $elasticmsUrl = $this->getArgumentString(self::ARG_ELASTICMS_URL);
        $this->jsonPath = $this->getArgumentString(self::ARG_CONFIG_FILE_PATH);
        $this->force = $this->getOptionBool(self::OPTION_FORCE);
        $this->cacheFolder = $this->getOptionString(self::OPTION_CACHE_FOLDER);
        $hash = $this->getOptionString(self::ARG_HASH_ALGO);
        $client = new Client($elasticmsUrl, $this->logger);
        $fileLocator = new FileLocator();
        $this->storageManager = new StorageManager($this->logger, $fileLocator, [], $hash);
        $this->coreApi = new CoreApi($client, $this->storageManager);
        $this->cache = new FilesystemAdapter();
        $this->mimeTypeGuesser = MimeTypeGuesser::getInstance();
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $token = $this->cache->get('my_auth_key', function (ItemInterface $item) use ($input) {
            $item->expiresAfter(3600);

            if (null === $input->getArgument(self::ARG_USERNAME)) {
                $input->setArgument(self::ARG_USERNAME, $this->io->askQuestion(new Question('Username')));
            }

            if (null === $input->getArgument(self::ARG_PASSWORD)) {
                $input->setArgument(self::ARG_PASSWORD, $this->io->askHidden('Password'));
            }
            $this->login();

            return $this->coreApi->getToken();
        });

        $this->coreApi->setToken($token);
        $this->username = $this->coreApi->user()->getProfileAuthenticated()->getUsername();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('Starting updating elasticms');

        $this->io->section('Load config');
        $cacheManager = new CacheManager($this->cacheFolder);
        $configManager = $this->loadConfigManager($cacheManager);
        $extractor = new Extractor($configManager, $cacheManager);
        $updateManager = new UpdateManager($this->coreApi, $configManager, $this->logger);

        $this->io->section('Start updates');
        $this->io->progressStart($extractor->extractDataCount());
        foreach ($extractor->extractData() as $extractedData) {
            $updateManager->update($extractedData, $this->force);
            $this->io->progressAdvance();
        }
        $this->io->progressFinish();
        $this->io->writeln('');

        $this->io->section('Save config');
        $configManager->save($this->jsonPath);

        return self::EXECUTE_SUCCESS;
    }

    private function login(): void
    {
        $username = $this->getArgumentString(self::ARG_USERNAME);
        $password = $this->getArgumentString(self::ARG_PASSWORD);
        $this->coreApi->authenticate($username, $password);
    }

    protected function loadConfigManager(CacheManager $cacheManager): ConfigManager
    {
        if (!\file_exists($this->jsonPath)) {
            throw new \RuntimeException(\sprintf('Config file %s not found', $this->jsonPath));
        }
        $contents = \file_get_contents($this->jsonPath);
        if (false === $contents) {
            throw new \RuntimeException('Unexpected false config file');
        }

        return ConfigManager::deserialize($contents, $cacheManager, $this->coreApi, $this->logger);
    }
}
