<?php

namespace Maximaster\Redmine2TuleapPlugin\Framework;

use Exception;
use Maximaster\Redmine2TuleapPlugin\Config\TransferConfig;
use Maximaster\Redmine2TuleapPlugin\Config\TransferConfigJsonFileBuilder;
use Maximaster\Redmine2TuleapPlugin\Enum\DatabaseEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\EntityTypeEnum;
use Maximaster\Redmine2TuleapPlugin\Repository\PluginRedmine2TuleapReferenceRepository;
use ParagonIE\EasyDB\EasyDB;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tuleap\Tracker\Import\Spotter;

abstract class GenericTransferCommand extends Command
{
    /** @var string */
    private $pluginDirectory;

    /** @var EasyDB */
    private $redmineDb;

    /** @var EasyDB */
    private $tuleapDb;

    /** @var PluginRedmine2TuleapReferenceRepository */
    protected $refRepo;

    /** @var TransferConfig */
    private $config;

    abstract protected function entityType(): EntityTypeEnum;

    abstract protected function transfer(InputInterface $input, SymfonyStyle $output): int;

    public function __construct(string $pluginDirectory, EasyDB $redmineDb, EasyDB $tuleapDb, PluginRedmine2TuleapReferenceRepository $refRepo)
    {
        parent::__construct();

        $this->pluginDirectory = $pluginDirectory;
        $this->redmineDb = $redmineDb;
        $this->tuleapDb = $tuleapDb;
        $this->refRepo = $refRepo;
    }

    protected function configure()
    {
        $this->getDefinition()->addOption(new InputOption('config', 'c', InputOption::VALUE_REQUIRED, 'Config file to load', 'redmine2tuleap.json'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // make tuleap think that we are importing something, so we can bypass user-binded fields checks
        // see \Tracker_FormElement_Field_List_Bind_Users::isExistingValue
        Spotter::instance()->startImport();
        $this->loadConfig($input->getOption('config'));
        return $this->transfer($input, new SymfonyStyle($input, $output));
    }

    /**
     * @param DatabaseEnum $dbName
     *
     * @return EasyDB
     *
     * @throws Exception
     */
    public function db(DatabaseEnum $dbName): EasyDB
    {
        switch ($dbName->getValue()) {
            case DatabaseEnum::REDMINE:
                return $this->redmineDb;

            case DatabaseEnum::TULEAP:
                return $this->tuleapDb;
        }

        throw new Exception(sprintf('Unknown database "%s"', $dbName->getValue()));
    }

    public function redmine(): EasyDB
    {
        return $this->redmineDb;
    }

    public function tuleap(): EasyDB
    {
        return $this->tuleapDb;
    }

    /**
     * @param string $sqlBatch
     *
     * @throws Exception
     */
    public function importSqlBatch(EasyDB $connection, string $sqlBatch): void
    {
        $pdo = $connection->getPdo();
        if ($pdo->exec($sqlBatch) === false) {
            throw new Exception(sprintf('Не удалось провести импорт SQL-пакета "%s": %s', mb_substr($sqlBatch, 0, 100), $pdo->errorInfo()[2]));
        }
    }

    public function markAsTransfered(string $redmineId, string $tuleapId): void
    {
        $this->refRepo->addReference($this->entityType(), $redmineId, $tuleapId);
    }

    public function transferedRedmineIdList(): array
    {
        return $this->refRepo->idsOfType($this->entityType());
    }

    private function loadConfig(string $fileName): void
    {
        $this->config = (new TransferConfigJsonFileBuilder())->buildFromFile($this->pluginDirectory . DIRECTORY_SEPARATOR . $fileName);
        $this->refRepo->setConfig($this->config);
    }

    public function config(): TransferConfig
    {
        return $this->config;
    }
}
