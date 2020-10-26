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
        EasyDB $redmineDb,
        EasyDB $tuleapDb,
        PluginRedmine2TuleapReferenceRepository $refRepo,
        string $contentDirectory,
        array $subTransfers
    ) {
        parent::__construct($redmineDb, $tuleapDb, $refRepo);

        $this->contentDirectory = $contentDirectory;
        $this->subTransfers = $subTransfers;
    }

    protected function configure()
    {
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

        foreach ($includedSubtransfers as $includedSubtransfer) {
            if (strpos($includedSubtransfer, 'redmine2tuleap:') !== 0) {
                foreach (explode(',', $includedSubtransfer) as $includedSubtransferItem) {
                    $includedSubtransfers[] = sprintf('redmine2tuleap:%s:transfer', $includedSubtransferItem);
                }
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
            $output->note(sprintf('Производим импорт для БД %s файла %s', $importItem['database'], basename($importItem['file'])));

            try {
                $this->importSqlBatch(
                    $this->db($importItem['database'] ? new DatabaseEnum($importItem['database']) : DatabaseEnum::DEFAULT()),
                    file_get_contents($importItem['file'])
                );
            } catch (Exception $e) {
                $output->error($e->getMessage());
                return -1;
            }
        }

        $allowAllSubtransfers = in_array('*', $includedSubtransfers);
        foreach ($this->subTransfers as $subTransfer) {
            if ($allowAllSubtransfers || in_array($subTransfer, $includedSubtransfers)) {
                $output->note(sprintf('Запускаем %s', $subTransfer));
                if ($this->subImport($subTransfer, $output) !== 0) {
                    $output->error(sprintf('Ошибка работы команды %s', $subTransfer));
                    return -1;
                }
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
