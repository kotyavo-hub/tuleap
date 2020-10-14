<?php

namespace Maximaster\RedmineTuleapImporter\Command;

use Maximaster\RedmineTuleapImporter\Enum\DatabaseEnum;
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

        $redmineConnection = $this->db->connection(DatabaseEnum::REDMINE);
        $redmineUsers = $redmineConnection->query('
            select
                id,
                login,
                firstname,
                lastname,
                `admin`,
                status,
                created_on,
                updated_on
            from users
            where `type` = "User"
        ');

        $progress = $output->createProgressBar(count($redmineUsers));

        foreach ($redmineUsers as $redmineUser) {
            $progress->advance();
        }

        return 0;
    }
}
