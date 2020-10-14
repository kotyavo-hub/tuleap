<?php

namespace Maximaster\RedmineTuleapImporter\Command;

use Exception;
use Maximaster\RedmineTuleapImporter\Database\Connection;
use Maximaster\RedmineTuleapImporter\Framework\GenericTransferCommand;
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

    public function __construct(string $contentDirectory, Connection $connection)
    {
        parent::__construct($connection);

        $this->contentDirectory = $contentDirectory;
    }

    protected function configure()
    {
        $def = $this->getDefinition();

        $def->addOptions([
            new InputOption('sql', 's', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY),
        ]);
    }

    protected function transfer(InputInterface $input, SymfonyStyle $ss): int
    {
        $imports = $input->getOption('sql');

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
            $this->connection->query(sprintf('SET GLOBAL max_allowed_packet=%d;', $packageSize));
        }

        foreach ($sqlImportQueue as $importItem) {
            $ss->note(sprintf('Производим импорт для БД %s файла %s', $importItem['database'], basename($importItem['file'])));

            try {
                $this->connection->useDatabase($importItem['database']);
                $this->connection->importDump(file_get_contents($importItem['file']));
            } catch (Exception $e) {
                $ss->error($e->getMessage());
                return -1;
            }
        }

        $this->subImport(TransferUsersCommand::getDefaultName(), $ss);

        $ss->note('OK?');
        return 0;
    }

    private function subImport(string $name, OutputInterface $output, array $input = []): int
    {
        return $this->getApplication()->find($name)->run(
            new ArrayInput($input),
            $output
        );
    }
}
