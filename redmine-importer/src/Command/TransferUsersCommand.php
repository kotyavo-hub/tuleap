<?php

namespace Maximaster\RedmineTuleapImporter\Command;

use Maximaster\RedmineTuleapImporter\Enum\DatabaseEnum;
use Maximaster\RedmineTuleapImporter\Enum\RedmineCustomFieldColumnEnum;
use Maximaster\RedmineTuleapImporter\Enum\RedmineCustomValueColumnEnum;
use Maximaster\RedmineTuleapImporter\Enum\RedmineEmailAddressColumnEnum;
use Maximaster\RedmineTuleapImporter\Enum\RedminePeopleInformationColumnEnum;
use Maximaster\RedmineTuleapImporter\Enum\RedmineTableEnum;
use Maximaster\RedmineTuleapImporter\Enum\RedmineUserColumnEnum;
use Maximaster\RedmineTuleapImporter\Enum\RedmineUserStatusEnum;
use Maximaster\RedmineTuleapImporter\Enum\TuleapTableEnum;
use Maximaster\RedmineTuleapImporter\Enum\TuleapUserColumnEnum;
use Maximaster\RedmineTuleapImporter\Enum\TuleapUserStatusEnum;
use Maximaster\RedmineTuleapImporter\Framework\GenericTransferCommand;
use Maximaster\RedmineTuleapImporter\Repository\RedmineCustomFieldRepository;
use MysqliDb;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TransferUsersCommand extends GenericTransferCommand
{
    /** @var RedmineCustomFieldRepository */
    private $cfRepo;

    public static function getDefaultName()
    {
        return 'app:users:transfer';
    }

    public function __construct(MysqliDb $db, RedmineCustomFieldRepository $cfRepo)
    {
        parent::__construct($db);
        $this->cfRepo = $cfRepo;
    }

    protected function transfer(InputInterface $input, SymfonyStyle $output): int
    {
        $ss = new SymfonyStyle($input, $output);
        $ss->note('Импорт пользователей');

        $cfs = $this->cfRepo->allOfUser(RedmineCustomFieldColumnEnum::NAME);

        $redmineConnection = $this->db->connection(DatabaseEnum::REDMINE);
        $redmineUsers = $redmineConnection->query('
            select
                `user`.' . RedmineUserColumnEnum::ID . ',
                `user`.' . RedmineUserColumnEnum::LOGIN . ',
                `user`.' . RedmineUserColumnEnum::FIRSTNAME . ',
                `user`.' . RedmineUserColumnEnum::LASTNAME . ',
                `user`.`' . RedmineUserColumnEnum::ADMIN . '`,
                `user`.' . RedmineUserColumnEnum::STATUS . ',
                `user`.' . RedmineUserColumnEnum::CREATED_ON . ',
                `user`.' . RedmineUserColumnEnum::UPDATED_ON . ',
                defaultEmail.' . RedmineEmailAddressColumnEnum::ADDRESS . ' as ' . TuleapUserColumnEnum::EMAIL . ',
                userInfo.' . RedminePeopleInformationColumnEnum::SKYPE . ' as ' . RedminePeopleInformationColumnEnum::SKYPE . ',
                userInfo.' . RedminePeopleInformationColumnEnum::PHONE . ' as ' . RedminePeopleInformationColumnEnum::PHONE . ',
                -- public ssh key
                publicSshKeyCf.' . RedmineCustomValueColumnEnum::VALUE . ' as ' . TuleapUserColumnEnum::AUTHORIZED_KEYS . ',
                -- gmail
                gmailCf.' . RedmineCustomValueColumnEnum::VALUE . ' as gmail
            from ' . RedmineTableEnum::USERS . ' `user`
            left join ' . RedmineTableEnum::EMAIL_ADDRESSES . ' as defaultEmail on
                defaultEmail.' . RedmineEmailAddressColumnEnum::USER_ID . ' = `user`.' . RedmineUserColumnEnum::ID . ' and
                defaultEmail.' . RedmineEmailAddressColumnEnum::IS_DEFAULT . ' = 1
            left join ' . RedmineTableEnum::PEOPLE_INFORMATION . ' userInfo on
                userInfo.user_id = `user`.' . RedmineUserColumnEnum::ID . '
            left join ' . RedmineTableEnum::CUSTOM_VALUES . ' publicSshKeyCf on
                publicSshKeyCf.' . RedmineCustomValueColumnEnum::CUSTOM_FIELD_ID . ' = ' . $cfs['Публичный ключ'][RedmineCustomFieldColumnEnum::ID] . ' and
                publicSshKeyCf.' . RedmineCustomValueColumnEnum::CUSTOMIZED_ID . ' = `user`.' . RedmineUserColumnEnum::ID . '
            left join ' . RedmineTableEnum::CUSTOM_VALUES . ' gmailCf on
                gmailCf.' . RedmineCustomValueColumnEnum::CUSTOM_FIELD_ID . ' = ' . $cfs['Gmail'][RedmineCustomFieldColumnEnum::ID] . ' and
                gmailCf.' . RedmineCustomValueColumnEnum::CUSTOMIZED_ID . ' = `user`.' . RedmineUserColumnEnum::ID . '
            where `user`.`type` = "User"
        ');

        $progress = $output->createProgressBar(count($redmineUsers));

        $userStatusTranfer = [
            RedmineUserStatusEnum::ACTIVE => TuleapUserStatusEnum::ACTIVE,
            RedmineUserStatusEnum::REGISTERED => TuleapUserStatusEnum::PENDING,
            RedmineUserStatusEnum::BLOCKED => TuleapUserStatusEnum::SUSPENDED,
        ];

        $this->db->query('alter table `' . TuleapTableEnum::USER . '` add column redmine_id int(11) default NULL');

        foreach ($redmineUsers as $redmineUser) {
            $redmineUserStatus = $redmineUser[RedmineUserColumnEnum::STATUS];
            $tuleapUser = [
                TuleapUserColumnEnum::REDMINE_ID => $redmineUser[RedmineUserColumnEnum::ID],
                TuleapUserColumnEnum::USER_NAME => $redmineUser[RedmineUserColumnEnum::LOGIN],
                TuleapUserColumnEnum::EMAIL => $redmineUser[TuleapUserColumnEnum::EMAIL],
                TuleapUserColumnEnum::REALNAME => sprintf('%s %s', $redmineUser[RedmineUserColumnEnum::LASTNAME], $redmineUser[RedmineUserColumnEnum::FIRSTNAME]),
                TuleapUserColumnEnum::STATUS => $userStatusTranfer[$redmineUserStatus] ?? TuleapUserStatusEnum::PENDING,
                TuleapUserColumnEnum::AUTHORIZED_KEYS => $redmineUser[TuleapUserColumnEnum::AUTHORIZED_KEYS],
                TuleapUserColumnEnum::LANGUAGE_ID => 'ru_RU',
            ];

            $existsUser = $this->db
                ->where(TuleapUserColumnEnum::REDMINE_ID, $tuleapUser[TuleapUserColumnEnum::REDMINE_ID])
                ->get(TuleapTableEnum::USER, 1, [TuleapUserColumnEnum::USER_ID])[0] ?? null;

            if ($existsUser) {
                $this->db
                    ->where(TuleapUserColumnEnum::REDMINE_ID, $existsUser[TuleapUserColumnEnum::USER_ID])
                    ->update(TuleapTableEnum::USER, $tuleapUser);
            } else {
                $this->db->insert(TuleapTableEnum::USER, $tuleapUser);
            }

            $progress->advance();
        }

        return 0;
    }
}
