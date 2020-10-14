<?php

namespace Maximaster\RedmineTuleapImporter\Command;

use Maximaster\RedmineTuleapImporter\Framework\GenericTransferCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TransferProjectsCommand extends GenericTransferCommand
{
    public static function getDefaultName()
    {
        return 'app:projects:transfer';
    }

    protected function transfer(InputInterface $input, SymfonyStyle $output): int
    {
        $ss = new SymfonyStyle($input, $output);
        $ss->note('Импорт проектов');

        return 0;
    }
}
