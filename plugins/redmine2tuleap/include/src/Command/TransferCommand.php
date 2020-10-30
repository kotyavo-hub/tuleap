<?php

namespace Maximaster\Redmine2TuleapPlugin\Command;

use Exception;
use Maximaster\Redmine2TuleapPlugin\Enum\DatabaseEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\EntityTypeEnum;
use Maximaster\Redmine2TuleapPlugin\Framework\GenericTransferCommand;
use Maximaster\Redmine2TuleapPlugin\Repository\PluginRedmine2TuleapReferenceRepository;
use ParagonIE\EasyDB\EasyDB;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TransferCommand extends GenericTransferCommand
{
    /** @var string */
    private $contentDirectory;

    /** @var string[] */
    private $subTransfers;

    public static function getDefaultName()
    {
        return 'redmine2tuleap:transfer';
    }

    /**
     * @param EasyDB|null $redmineDb
     * @param EasyDB $tuleapDb
     * @param PluginRedmine2TuleapReferenceRepository $refRepo
     * @param string $contentDirectory
     * @param string[] $subTransfers
     */
    public function __construct(
        string $pluginDirectory,
        EasyDB $redmineDb,
        EasyDB $tuleapDb,
        PluginRedmine2TuleapReferenceRepository $refRepo,
        string $contentDirectory,
        array $subTransfers
    ) {
        parent::__construct($pluginDirectory, $redmineDb, $tuleapDb, $refRepo);

        $this->contentDirectory = $contentDirectory;
        $this->subTransfers = $subTransfers;
    }

    protected function configure()
    {
        parent::configure();

        $def = $this->getDefinition();

        $def->addOptions([
            new InputOption('sql', 's', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY),
            new InputOption('include', 'i', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Included sub-transfers')
        ]);
    }

    protected function transfer(InputInterface $input, SymfonyStyle $output): int
    {
        $imports = $input->getOption('sql');
        $includedSubtransfers = $input->getOption('include') ?? [];

        foreach ($includedSubtransfers as $idx => $includedSubtransfer) {
            if (strpos($includedSubtransfer, 'redmine2tuleap:') !== 0) {
                foreach (explode(',', $includedSubtransfer) as $includedSubtransferItem) {
                    $includedSubtransfers[] = sprintf('redmine2tuleap:%s:transfer', $includedSubtransferItem);
                    unset($includedSubtransfers[$idx]);
                }
            } elseif ($includedSubtransfer === '*') {
                $includedSubtransfers = $this->subTransfers;
                break;
            }
        }

        foreach ($includedSubtransfers as $includedSubtransfer) {
            if (!in_array($includedSubtransfer, $this->subTransfers)) {
                $output->error(sprintf('Unknown sub-transfer "%s". Allowed: %s', $includedSubtransfer, implode(', ', $this->subTransfers)));
                return -1;
            }
        }

        $sqlImportQueue = [];
        foreach ($imports as $importDefinition) {
            if (strpos($importDefinition, ':') === false) {
                $output->error('Значение для --sql должно содержать два значение через ":": 1) Имя базы данных; 2) Путь к SQL-файлу');
                return -1;
            }

            [$databaseName, $sqlFile] = explode(':', $importDefinition, 2);

            $sqlFilePath = realpath($this->contentDirectory . DIRECTORY_SEPARATOR . $sqlFile);
            if (!$sqlFilePath) {
                $output->error(sprintf('Не удалось найти файл "%s" в директории "%s"', $sqlFile, $this->contentDirectory));
                return -1;
            }

            $sqlImportQueue[] = [
                'database' => $databaseName,
                'file' => $sqlFilePath,
            ];
        }

        foreach ($sqlImportQueue as $importItem) {
            $output->note(sprintf('Importing file %s into %s database', basename($importItem['file']), $importItem['database']));

            try {
                $this->importSqlBatch(
                    $this->db($importItem['database'] ? new DatabaseEnum($importItem['database']) : DatabaseEnum::DEFAULT()),
                    file_get_contents($importItem['file'])
                );

                if ($importItem['database'] === DatabaseEnum::TULEAP) {
                    $this->refRepo->clear();
                }
            } catch (Exception $e) {
                $output->error($e->getMessage());
                return -1;
            }
        }

        foreach ($includedSubtransfers as $includedSubtransfer) {
            $output->section(sprintf('Starting sub-import %s', $includedSubtransfer));
            if ($this->subImport($includedSubtransfer, $output) !== 0) {
                $output->error(sprintf('Failed sub-import %s', $includedSubtransfer));
                return -1;
            }
        }

        $output->note('OK?');
        return 0;
    }

    private function subImport(string $name, OutputInterface $output, array $input = []): int
    {
        return $this->getApplication()->find($name)->run(new ArrayInput($input), $output);
    }

    protected function entityType(): EntityTypeEnum
    {
        return new EntityTypeEnum(null);
    }
}
