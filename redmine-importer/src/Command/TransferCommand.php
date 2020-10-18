<?php

namespace Maximaster\RedmineTuleapImporter\Command;

use Exception;
use Maximaster\RedmineTuleapImporter\Enum\DatabaseEnum;
use Maximaster\RedmineTuleapImporter\Framework\GenericTransferCommand;
use MysqliDb;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TransferCommand extends GenericTransferCommand
{
    /** @var string */
    private $contentDirectory;

    public static function getDefaultName()
    {
        return 'app:transfer';
    }

    public function __construct(MysqliDb $redmineDb, MysqliDb $tuleapDb, string $contentDirectory)
    {
        parent::__construct($redmineDb, $tuleapDb);
        $this->contentDirectory = $contentDirectory;
    }

    protected function configure()
    {
        $def = $this->getDefinition();

        $def->addOptions([
            new InputOption('sql', 's', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY),
            new InputOption('include', 'i', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Included sub-transfers')
        ]);
    }

    protected function transfer(InputInterface $input, SymfonyStyle $ss): int
    {
        $imports = $input->getOption('sql');
        $includedSubtransfers = $input->getOption('include') ?? [];

        $sqlImportQueue = [];
        $maxFileSize = 0;
        foreach ($imports as $importDefinition) {
            if (strpos($importDefinition, ':') === false) {
                $ss->error('Значение для --sql должно содержать два значение через ":": 1) Имя базы данных; 2) Путь к SQL-файлу');
                return -1;
            }

            [$databaseName, $sqlFile] = explode(':', $importDefinition, 2);

            $sqlFilePath = realpath($this->contentDirectory . DIRECTORY_SEPARATOR . $sqlFile);
            if (!$sqlFilePath) {
                $ss->error(sprintf('Не удалось найти файл "%s" в директории "%s"', $sqlFile, $this->contentDirectory));
                return -1;
            }

            $sqlImportQueue[] = [
                'database' => $databaseName,
                'file' => $sqlFilePath,
            ];

            $fileSize = filesize($sqlFilePath);
            if ($fileSize > $maxFileSize) {
                $maxFileSize = $fileSize;
            }
        }

        if ($maxFileSize) {
            // с запасом относительно максимального размера файла
            $packageSize = 4 * max(32 * 1024 * 1024, $maxFileSize);

            $ss->note(sprintf('Устанавливаем размер MySQL пакета данных: %d', $packageSize));
            $this->tuleap()->query(sprintf('SET GLOBAL max_allowed_packet=%d;', $packageSize));
        }

        foreach ($sqlImportQueue as $importItem) {
            $ss->note(sprintf('Производим импорт для БД %s файла %s', $importItem['database'], basename($importItem['file'])));

            try {
                $this->importSqlBatch(
                    $this->db($importItem['database'] ? new DatabaseEnum($importItem['database']) : DatabaseEnum::DEFAULT()),
                    file_get_contents($importItem['file'])
                );
            } catch (Exception $e) {
                $ss->error($e->getMessage());
                return -1;
            }
        }

        $subTransfers = [
            TransferUsersCommand::getDefaultName(),
        ];

        $allowAllSubtransfers = in_array('*', $includedSubtransfers);

        foreach ($subTransfers as $subTransfer) {
            if ($allowAllSubtransfers || in_array($subTransfer, $includedSubtransfers)) {
                $ss->note(sprintf('Запускаем %s', $subTransfer));
                if ($this->subImport($subTransfer, $ss) !== 0) {
                    $ss->error(sprintf('Ошибка работы команды %s', $subTransfer));
                    return -1;
                }
            }
        }

        $ss->note('OK?');
        return 0;
    }

    private function subImport(string $name, OutputInterface $output, array $input = []): int
    {
        return $this->getApplication()->find($name)->run(new ArrayInput($input), $output);
    }
}
