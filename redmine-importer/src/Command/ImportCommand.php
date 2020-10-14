<?php

namespace Maximaster\RedmineTuleapImporter\Command;

use mysqli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportCommand extends Command
{
    /** @var string */
    private $directory;

    private $connection;

    public static function getDefaultName()
    {
        return 'app:import';
    }

    public function __construct(string $directory, mysqli $connection)
    {
        parent::__construct();
        $this->connection = $connection;
    }

    protected function configure()
    {
        $def = $this->getDefinition();

        $def->addOptions([
            new InputOption('sql-file', 'f', InputOption::VALUE_REQUIRED),
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $connection = new mysqli(getenv('DHOST'), 'root', getenv('MYSQL_ROOT_PASSWORD'));

        $skipResults = function () use ($connection) {
            while ($connection->more_results()) {
                $connection->next_result();
            }
        };

        $res = $connection->query(sprintf('SET GLOBAL max_allowed_packet=%d;', 1 * 1024 * 1024 * 1024)); // 1G
        if ($res === false) {
            throw new Exception(sprintf('Не удалось увеличить лимит на размер пакета MySQL-запросов: %s', $connection->error));
        }

        if (!$connection->multi_query(file_get_contents(__DIR__.'/../schema/redmine.db.sql'))) {
            throw new Exception(sprintf('Не удалось создать базу данных для Redmine: %s', $connection->error));
        }

        $skipResults();

        if (!$connection->select_db('redmine')) {
            throw new Exception(sprintf('Не удалось переключиться на Redmine базу данных: %s', $connection->error));
        }

        if (!$connection->multi_query(file_get_contents(__DIR__.'/../redmine.sql'))) {
            throw new Exception(sprintf('Не удалось импортировать Redmine дамп базы: %s', $connection->error));
        }

        $skipResults();

        echo 'OK?';

    }
}
