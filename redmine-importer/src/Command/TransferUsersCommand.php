<?php

namespace Maximaster\RedmineTuleapImporter\Command;

use Exception;
use Maximaster\RedmineTuleapImporter\Enum\RedmineCustomFieldColumnEnum;
use Maximaster\RedmineTuleapImporter\Enum\RedmineCustomValueColumnEnum;
use Maximaster\RedmineTuleapImporter\Enum\RedmineEmailAddressColumnEnum;
use Maximaster\RedmineTuleapImporter\Enum\RedminePeopleInformationColumnEnum;
use Maximaster\RedmineTuleapImporter\Enum\RedmineTableEnum;
use Maximaster\RedmineTuleapImporter\Enum\RedmineUserColumnEnum;
use Maximaster\RedmineTuleapImporter\Enum\RedmineUserStatusEnum;
use Maximaster\RedmineTuleapImporter\Enum\TuleapTableEnum;
use Maximaster\RedmineTuleapImporter\Enum\TuleapUserAccessColumnEnum;
use Maximaster\RedmineTuleapImporter\Enum\TuleapUserColumnEnum;
use Maximaster\RedmineTuleapImporter\Enum\TuleapUserGroupColumnEnum;
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

    public function __construct(MysqliDb $redmineDb, MysqliDb $tuleapDb, RedmineCustomFieldRepository $cfRepo)
    {
        parent::__construct($redmineDb, $tuleapDb);
        $this->cfRepo = $cfRepo;
    }

    /**
     * @param InputInterface $input
     * @param SymfonyStyle $output
     *
     * @return int
     *
     * @throws Exception
     */
    protected function transfer(InputInterface $input, SymfonyStyle $output): int
    {
        $ss = new SymfonyStyle($input, $output);
        $ss->note('Импорт пользователей');

        $cfs = $this->cfRepo->allOfUser(RedmineCustomFieldColumnEnum::NAME);

        $redmineDb = $this->redmine();
        $tuleapDb = $this->tuleap();

        $redmineUsers = $redmineDb->query('
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

        $tuleapDb->query('alter table `' . TuleapTableEnum::USER . '` add column redmine_id int(11) default NULL');

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

            $redmineUserId = $tuleapUser[TuleapUserColumnEnum::REDMINE_ID];

            $existsUser = $tuleapDb
                ->where(TuleapUserColumnEnum::REDMINE_ID, $redmineUserId)
                ->get(TuleapTableEnum::USER, 1, [TuleapUserColumnEnum::USER_ID])[0] ?? null;

            $existsUserByLogin = $tuleapDb
                ->where(TuleapUserColumnEnum::USER_NAME, $redmineUser[RedmineUserColumnEnum::LOGIN])
                ->get(TuleapTableEnum::USER, 1, [TuleapUserColumnEnum::USER_ID])[0] ?? null;

            if ($existsUserByLogin) {
                $tuleapUser[TuleapUserColumnEnum::USER_NAME] = sprintf('redmine_%s', $tuleapUser[TuleapUserColumnEnum::USER_NAME]);
            }

            if ($existsUser) {
                $updated = $tuleapDb
                    ->where(TuleapUserColumnEnum::REDMINE_ID, $existsUser[TuleapUserColumnEnum::USER_ID])
                    ->update(TuleapTableEnum::USER, $tuleapUser);

                if (!$updated) {
                    $output->error(sprintf('Не удалось обновить пользователя %d: %s', $redmineUserId, $tuleapDb->getLastError()));
                    return -1;
                }
            } else {
                if (!$tuleapDb->insert(TuleapTableEnum::USER, $tuleapUser)) {
                    $output->error(sprintf('Не удалось создать пользователя %d: %s', $redmineUserId, $tuleapDb->getLastError()));
                    return -1;
                }
                $existsUser[TuleapUserColumnEnum::USER_ID] = $tuleapDb->getInsertId();
            }

            $tuleapUserId = $existsUser[TuleapUserColumnEnum::USER_ID];

            // Без меток доступа не работают выборки в админке - пользователи не отображаются
            $userAccess = $tuleapDb
                ->where(TuleapUserAccessColumnEnum::USER_ID, $tuleapUserId)
                ->get(TuleapTableEnum::USER_ACCESS, 1);

            if (!$userAccess) {
                $userAccessInserted = $tuleapDb->insert(TuleapTableEnum::USER_ACCESS, [
                    TuleapUserAccessColumnEnum::USER_ID => $tuleapUserId,
                ]);

                if (!$userAccessInserted) {
                    $output->error(sprintf('Не удалось создать запись о времени доступа для %d: %s', $redmineUserId, $tuleapDb->getLastError()));
                    return -1;
                }
            }

            if ($redmineUser[RedmineUserColumnEnum::ADMIN]) {
                $inserted = $tuleapDb->insert(TuleapTableEnum::USER_GROUP, [
                    TuleapUserGroupColumnEnum::USER_ID => $tuleapUserId,
                    TuleapUserGroupColumnEnum::ADMIN_FLAGS => 'A',
                    TuleapUserGroupColumnEnum::BUG_FLAGS => 2,
                    TuleapUserGroupColumnEnum::FORUM_FLAGS => 2,
                    TuleapUserGroupColumnEnum::PROJECT_FLAGS => 2,
                    TuleapUserGroupColumnEnum::PATCH_FLAGS => 2,
                    TuleapUserGroupColumnEnum::SUPPORT_FLAGS => 2,
                    TuleapUserGroupColumnEnum::FILE_FLAGS => 2,
                    TuleapUserGroupColumnEnum::WIKI_FLAGS => 2,
                    TuleapUserGroupColumnEnum::SVN_FLAGS => 2,
                    TuleapUserGroupColumnEnum::NEWS_FLAGS => 2,
                ]);

                if (!$inserted) {
                    $output->error(sprintf('Не удалось сохранить метку администратора для %d: %s', $tuleapUserId, $tuleapDb->getLastError()));
                    return -1;
                }
            }

            $progress->advance();
        }

        return 0;
    }
}
