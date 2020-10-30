<?php

namespace Maximaster\Redmine2TuleapPlugin\Command;

use Exception;
use Maximaster\Redmine2TuleapPlugin\Enum\EntityTypeEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineCustomFieldColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineCustomValueColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineEmailAddressColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedminePeopleInformationColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineTableEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineUserColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineUserStatusEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\TuleapTableEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\TuleapUserColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\TuleapUserStatusEnum;
use Maximaster\Redmine2TuleapPlugin\Framework\GenericTransferCommand;
use Maximaster\Redmine2TuleapPlugin\Repository\PluginRedmine2TuleapReferenceRepository;
use Maximaster\Redmine2TuleapPlugin\Repository\RedmineCustomFieldRepository;
use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\EasyStatement;
use PFUser;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tuleap\Project\UserPermissionsDao;
use UserManager;

class TransferUsersCommand extends GenericTransferCommand
{
    public const STATUS_CONVERSION = [
        RedmineUserStatusEnum::ACTIVE => TuleapUserStatusEnum::ACTIVE,
        RedmineUserStatusEnum::REGISTERED => TuleapUserStatusEnum::PENDING,
        RedmineUserStatusEnum::BLOCKED => TuleapUserStatusEnum::SUSPENDED,
    ];

    /** @var RedmineCustomFieldRepository */
    private $cfRepo;

    /** @var UserManager */
    private $userManager;

    /** @var UserPermissionsDao */
    private $userPermDao;

    public static function getDefaultName()
    {
        return 'redmine2tuleap:users:transfer';
    }

    protected function entityType(): EntityTypeEnum
    {
        return EntityTypeEnum::USER();
    }

    public function __construct(
        string $pluginDirectory,
        EasyDB $redmineDb,
        EasyDB $tuleapDb,
        PluginRedmine2TuleapReferenceRepository $refRepo,
        RedmineCustomFieldRepository $cfRepo,
        UserManager $userManager,
        UserPermissionsDao $userPermDao
    ) {
        parent::__construct($pluginDirectory, $redmineDb, $tuleapDb, $refRepo);
        $this->cfRepo = $cfRepo;
        $this->userManager = $userManager;
        $this->userPermDao = $userPermDao;
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

        $cfs = $this->cfRepo->allOfUser(RedmineCustomFieldColumnEnum::NAME);

        $redmineDb = $this->redmine();
        $tuleapDb = $this->tuleap();

        $redmineUsersQuery = '
            SELECT
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
            FROM ' . RedmineTableEnum::USERS . ' `user`
            LEFT JOIN ' . RedmineTableEnum::EMAIL_ADDRESSES . ' as defaultEmail on
                defaultEmail.' . RedmineEmailAddressColumnEnum::USER_ID . ' = `user`.' . RedmineUserColumnEnum::ID . ' and
                defaultEmail.' . RedmineEmailAddressColumnEnum::IS_DEFAULT . ' = 1
            LEFT JOIN ' . RedmineTableEnum::PEOPLE_INFORMATION . ' userInfo on
                userInfo.user_id = `user`.' . RedmineUserColumnEnum::ID . '
            LEFT JOIN ' . RedmineTableEnum::CUSTOM_VALUES . ' publicSshKeyCf on
                publicSshKeyCf.' . RedmineCustomValueColumnEnum::CUSTOM_FIELD_ID . ' = ' . $cfs['Публичный ключ'][RedmineCustomFieldColumnEnum::ID] . ' and
                publicSshKeyCf.' . RedmineCustomValueColumnEnum::CUSTOMIZED_ID . ' = `user`.' . RedmineUserColumnEnum::ID . '
            LEFT JOIN ' . RedmineTableEnum::CUSTOM_VALUES . ' gmailCf on
                gmailCf.' . RedmineCustomValueColumnEnum::CUSTOM_FIELD_ID . ' = ' . $cfs['Gmail'][RedmineCustomFieldColumnEnum::ID] . ' and
                gmailCf.' . RedmineCustomValueColumnEnum::CUSTOMIZED_ID . ' = `user`.' . RedmineUserColumnEnum::ID . '
            WHERE
                `user`.`type` = "User"
        ';

        $redmineUsersQueryValues = [];
        if ($alreadyTransferedUserIds = $this->transferedRedmineIdList()) {
            $redmineUsersQuery .= ' AND ' . EasyStatement::open()->in('`user`.id not in (?*)',
                    $alreadyTransferedUserIds);
            $redmineUsersQueryValues = array_merge($redmineUsersQueryValues, $alreadyTransferedUserIds);
        }

        $redmineUsers = $redmineDb->run($redmineUsersQuery, ...$redmineUsersQueryValues);

        if (!$redmineUsers) {
            $output->note('Have no users to import');
            return 0;
        }

        $usersCnt = count($redmineUsers);

        $ss->section(sprintf('Transfering %d user%s', $usersCnt, $usersCnt > 1 ? 's' : ''));

        $progress = $output->createProgressBar($usersCnt);

        foreach ($redmineUsers as $redmineUser) {
            try {
                $redmineUserId = $redmineUser[RedmineUserColumnEnum::ID];
                $redmineUserStatusId = $redmineUser[RedmineUserColumnEnum::STATUS];

                $tuleapUser = new PFUser();
                $tuleapUser->setUserName($redmineUser[RedmineUserColumnEnum::LOGIN]);
                $tuleapUser->setEmail($redmineUser[TuleapUserColumnEnum::EMAIL]);
                $tuleapUser->setRealName(sprintf(
                    '%s %s', $redmineUser[RedmineUserColumnEnum::LASTNAME],
                    $redmineUser[RedmineUserColumnEnum::FIRSTNAME]
                ));
                $tuleapUser->setStatus(self::STATUS_CONVERSION[$redmineUserStatusId] ?? TuleapUserStatusEnum::PENDING);
                $tuleapUser->setAuthorizedKeys($this->prepareTuleapSshKeys($redmineUser[TuleapUserColumnEnum::AUTHORIZED_KEYS]));
                $tuleapUser->setLanguageID($this->config()->language());

                $hasLoginMatch = $tuleapDb->cell(
                    'SELECT ' . TuleapUserColumnEnum::USER_NAME . ' ' .
                    'FROM ' . TuleapTableEnum::USER . ' ' .
                    'WHERE ' . TuleapUserColumnEnum::USER_NAME . ' = ?',
                    $redmineUser[RedmineUserColumnEnum::LOGIN]
                );

                if ($hasLoginMatch) {
                    $tuleapUser->setUserName(
                        sprintf('redmine_%s', $tuleapUser->getUserName())
                    );
                }

                if (!$this->userManager->createAccount($tuleapUser)) {
                    $output->error(
                        sprintf(
                            'Не удалось создать пользователя %d: %d %d %s',
                            $redmineUserId,
                            ...$tuleapDb->errorInfo()
                        ))
                    ;
                    return -1;
                }

                $tuleapUserId = $tuleapUser->getId();

                if ($redmineUser[RedmineUserColumnEnum::ADMIN]) {
                    $this->userPermDao->addUserAsProjectMember(1, $tuleapUserId);
                    $this->userPermDao->addUserAsProjectAdmin(1, $tuleapUserId);
                }

                $this->markAsTransfered((string) $redmineUserId, (string) $tuleapUserId);
            } catch (Exception $e) {
                $output->error($e->getMessage());
            }

            $progress->advance();
        }

        return 0;
    }

    private function prepareTuleapSshKeys(?string $sshKeys): string
    {
        return empty($sshKeys) ? '' : implode('###', explode("\n", $sshKeys));
    }
}
