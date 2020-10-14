<?php

namespace Maximaster\RedmineTuleapImporter\Framework;

use MysqliDb;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class GenericTransferCommand extends Command
{
    /** @var MysqliDb */
    protected $db;

    abstract protected function transfer(InputInterface $input, SymfonyStyle $output): int;

    public function __construct(MysqliDb $db)
    {
        parent::__construct();

        $this->db = $db;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        return $this->transfer($input, new SymfonyStyle($input, $output));
    }
}
