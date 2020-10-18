<?php

namespace Maximaster\RedmineTuleapImporter\Framework;

use Exception;
use Maximaster\RedmineTuleapImporter\Enum\DatabaseEnum;
use MysqliDb;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class GenericTransferCommand extends Command
{
    /** @var MysqliDb */
    private $redmineDb;

    /** @var MysqliDb */
    private $tuleapDb;

    abstract protected function transfer(InputInterface $input, SymfonyStyle $output): int;

    public function __construct(MysqliDb $redmineDb, MysqliDb $tuleapDb)
    {
        parent::__construct();

        $this->redmineDb = $redmineDb;
        $this->tuleapDb = $tuleapDb;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        return $this->transfer($input, new SymfonyStyle($input, $output));
    }

    /**
     * @param DatabaseEnum $dbName
     *
     * @return MysqliDb
     *
     * @throws Exception
     */
    public function db(DatabaseEnum $dbName): MysqliDb
    {
        switch ($dbName->getValue()) {
            case DatabaseEnum::REDMINE:
                return $this->redmineDb;

            case DatabaseEnum::TULEAP:
                return $this->tuleapDb;
        }

        throw new Exception(sprintf('Unknown database "%s"', $dbName->getValue()));
    }

    public function redmine(): MysqliDb
    {
        return $this->redmineDb;
    }

    public function tuleap(): MysqliDb
    {
        return $this->tuleapDb;
    }

    /**
     * @param string $sqlBatch
     *
     * @throws Exception
     */
    public function importSqlBatch(MysqliDb $connection, string $sqlBatch): void
    {
        $mysqli = $connection->mysqli();
        if (!$mysqli->multi_query($sqlBatch)) {
            throw new Exception(sprintf('Не удалось провести импорт SQL-пакета "%s": %s', mb_substr($sqlBatch, 0, 100), $mysqli->error));
        }

        while ($mysqli->more_results()) {
            $mysqli->next_result();
        }
    }
}
