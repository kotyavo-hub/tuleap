<?php

namespace Maximaster\RedmineTuleapImporter\Command;

use Maximaster\RedmineTuleapImporter\Framework\GenericTransferCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TransferUsersCommand extends GenericTransferCommand
{
    public static function getDefaultName()
    {
        return 'app:users:transfer';
    }

    protected function transfer(InputInterface $input, SymfonyStyle $output): int
    {
        $ss = new SymfonyStyle($input, $output);
        $ss->note('Импорт пользователей');

        return 0;
    }
}
