<?php

namespace Maximaster\RedmineTuleapImporter\Framework;

use Maximaster\RedmineTuleapImporter\Database\Connection;
use Maximaster\RedmineTuleapImporter\Enum\DatabaseEnum;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class GenericTransferCommand extends Command
{
    /** @var Connection */
    protected $connection;

    abstract protected function transfer(InputInterface $input, SymfonyStyle $output): int;

    public function __construct(Connection $connection)
    {
        parent::__construct();

        $this->connection = $connection;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        return $this->transfer($input, new SymfonyStyle($input, $output));
    }
}
