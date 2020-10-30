<?php

namespace Maximaster\Redmine2TuleapPlugin\Command;

use Exception;
use Maximaster\Redmine2TuleapPlugin\Enum\EntityTypeEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\FieldListBindUserColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineCustomFieldColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineCustomFieldFormatEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineCustomFieldTypeEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineCustomValueColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineEnumerationTypeEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineIssueColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineIssueStatusColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineTableEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\TuleapTableEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\TuleapTrackerFieldColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\TuleapUserColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\TuleapUserStatusEnum;
use Maximaster\Redmine2TuleapPlugin\Framework\GenericTransferCommand;
use Maximaster\Redmine2TuleapPlugin\Repository\PluginRedmine2TuleapReferenceRepository;
use Maximaster\Redmine2TuleapPlugin\Repository\PluginRedmine2TuleapTrackerFieldListBindUsersBackupRepository;
use Maximaster\Redmine2TuleapPlugin\Repository\RedmineCustomFieldRepository;
use Maximaster\Redmine2TuleapPlugin\Repository\RedmineEnumerationRepository;
use Maximaster\Redmine2TuleapPlugin\Repository\RedmineIssueStatusRepository;
use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\EasyStatement;
use PFUser;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tracker;
use Tracker_Artifact;
use Tracker_Artifact_ChangesetValue_Text;
use Tracker_ArtifactFactory;
use Tracker_FormElement_Field;
use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_List_Bind_Users;
use Tracker_FormElement_Field_MultiSelectbox;
use Tracker_FormElement_Field_Selectbox;
use Tracker_FormElement_FieldDao;
use Tracker_FormElementFactory;
use Tracker_Workflow_WorkflowUser;
use TrackerFactory;
use Netcarver\Textile;
use Tuleap\Project\UserPermissionsDao;
use UserManager;

class TransferIssuesCommand extends GenericTransferCommand
{
    /** @var Tracker_ArtifactFactory */
    private $trackerArtifactFactory;

    /** @var TrackerFactory */
    private $trackerFactory;

    /** @var RedmineCustomFieldRepository */
    private $cfRepo;

    /** @var Tracker_FormElementFactory */
    private $formElementFactory;
    /**
     * @var RedmineIssueStatusRepository
     */
    private $issueStatusRepo;

    /** @var RedmineEnumerationRepository */
    private $redmineEnumRepo;

    /** @var Textile\Parser */
    private $textileParser;

    /** @var int */
    private $serverRequestTime;

    /** @var PluginRedmine2TuleapTrackerFieldListBindUsersBackupRepository */
    private $temporaryRebindedUserFieldsRepo;

    /** @var UserPermissionsDao */
    private $userPermDao;

    /** @var array */
    private $projectMembers = [];

    /** @var UserManager */
    private $userManager;

    public static function getDefaultName()
    {
        return 'redmine2tuleap:issues:transfer';
    }

    protected function entityType(): EntityTypeEnum
    {
        return EntityTypeEnum::ISSUE();
    }

    public function __construct(
        string $pluginDirectory,
        EasyDB $redmineDb,
        EasyDB $tuleapDb,
        PluginRedmine2TuleapReferenceRepository $refRepo,
        Tracker_ArtifactFactory $trackerArtifactFactory,
        TrackerFactory $trackerFactory,
        RedmineCustomFieldRepository $cfRepo,
        Tracker_FormElementFactory $formElementFactory,
        RedmineIssueStatusRepository $issueStatusRepo,
        RedmineEnumerationRepository $redmineEnumRepo,
        Textile\Parser $textileParser,
        PluginRedmine2TuleapTrackerFieldListBindUsersBackupRepository $temporaryRebindedUserFieldsRepo,
        UserPermissionsDao $userPermDao,
        UserManager $userManager
    ) {
        parent::__construct($pluginDirectory, $redmineDb, $tuleapDb, $refRepo);
        $this->trackerArtifactFactory = $trackerArtifactFactory;
        $this->trackerFactory = $trackerFactory;
        $this->cfRepo = $cfRepo;
        $this->formElementFactory = $formElementFactory;
        $this->issueStatusRepo = $issueStatusRepo;
        $this->redmineEnumRepo = $redmineEnumRepo;
        $this->textileParser = $textileParser;
        $this->temporaryRebindedUserFieldsRepo = $temporaryRebindedUserFieldsRepo;
        $this->userPermDao = $userPermDao;
        $this->userManager = $userManager;
    }

    protected function transfer(InputInterface $input, SymfonyStyle $output): int
    {
        $redmineDb = $this->redmine();

        $redmineAdminUserId = $this->refRepo->findTuleapId(EntityTypeEnum::USER(), 1);
        if (!$redmineAdminUserId) {
            $output->error('Transfer users firts');
            return -1;
        }

        $importedProjectIds = $this->refRepo->idsOfType(EntityTypeEnum::PROJECT());
        if (empty($importedProjectIds)) {
            $output->warning('Transfer some projects first');
            return 0;
        }

        $issuesQuery = '
            SELECT ' . implode(', ', [
                RedmineIssueColumnEnum::ID,
                // RedmineIssueColumnEnum::TRACKER_ID,
                RedmineIssueColumnEnum::PROJECT_ID,
                RedmineIssueColumnEnum::SUBJECT,
                RedmineIssueColumnEnum::DESCRIPTION,
                RedmineIssueColumnEnum::DUE_DATE,
                // RedmineIssueColumnEnum::CATEGORY_ID,
                RedmineIssueColumnEnum::STATUS_ID,
                RedmineIssueColumnEnum::ASSIGNED_TO_ID,
                RedmineIssueColumnEnum::PRIORITY_ID,
                // RedmineIssueColumnEnum::FIXED_VERSION_ID,
                RedmineIssueColumnEnum::AUTHOR_ID,
                // RedmineIssueColumnEnum::LOCK_VERSION,
                RedmineIssueColumnEnum::CREATED_ON,
                RedmineIssueColumnEnum::UPDATED_ON,
                RedmineIssueColumnEnum::START_DATE,
                // RedmineIssueColumnEnum::DONE_RATIO,
                RedmineIssueColumnEnum::ESTIMATED_HOURS,
                RedmineIssueColumnEnum::PARENT_ID,
                // RedmineIssueColumnEnum::ROOT_ID,
                // RedmineIssueColumnEnum::LFT,
                // RedmineIssueColumnEnum::RGT,
                // RedmineIssueColumnEnum::IS_PRIVATE,
                // RedmineIssueColumnEnum::CLOSED_ON,
                // RedmineIssueColumnEnum::EXPIRATION_DATE,
                // RedmineIssueColumnEnum::FIRST_RESPONSE_DATE,
                // RedmineIssueColumnEnum::ISSUE_SLA,
                // RedmineIssueColumnEnum::SYNCHRONY_ID,
                // RedmineIssueColumnEnum::SYNCHRONIZED_AT,
            ]). '
            FROM ' . RedmineTableEnum::ISSUES . '
        ';

        $condition = EasyStatement::open();

        if ($transferedIssueIds = $this->transferedRedmineIdList()) {
            $condition->andIn(RedmineIssueColumnEnum::ID . ' not in (?*)', $transferedIssueIds);
        }

        $condition->andIn(RedmineIssueColumnEnum::PROJECT_ID . ' in (?*)', $importedProjectIds);

        $issuesQuery .= ' WHERE ' . $condition->sql();

        $redmineIssues = $redmineDb->run($issuesQuery, ...$condition->values());

        if (empty($redmineIssues)) {
            $output->note('Nothing to import');
            return 0;
        }

        $output->section(sprintf('Transfering %d issues of %d projects', count($redmineIssues), count($importedProjectIds)));

        $redmineIssueIds = array_column($redmineIssues, RedmineIssueColumnEnum::ID);
        $redmineIssues = array_combine($redmineIssueIds, $redmineIssues);

        $customValuesQueryCondition = EasyStatement::open()
            ->in(RedmineCustomValueColumnEnum::CUSTOMIZED_ID . ' in (?*)', $redmineIssueIds)
            ->andWith(RedmineCustomValueColumnEnum::CUSTOMIZED_TYPE . ' = ?', RedmineCustomFieldTypeEnum::ISSUE);

        $customValueRows = $redmineDb->run(
            '
                SELECT *
                FROM ' . RedmineTableEnum::CUSTOM_VALUES . '
                WHERE ' . $customValuesQueryCondition->sql() . '
            ',
            ...$customValuesQueryCondition->values()
        );

        foreach ($customValueRows as $customValueRow) {
            $issueId = $customValueRow[RedmineCustomValueColumnEnum::CUSTOMIZED_ID];
            if (empty($redmineIssues[$issueId])) {
                continue;
            }

            $customField = $this->cfRepo->get($customValueRow[RedmineCustomValueColumnEnum::CUSTOM_FIELD_ID]);

            $fieldLabel = $customField[RedmineCustomFieldColumnEnum::NAME];
            $customValue = $customValueRow[RedmineCustomValueColumnEnum::VALUE];

            $issue = &$redmineIssues[$issueId];

            if ($customField[RedmineCustomFieldColumnEnum::MULTIPLE] && !array_key_exists($fieldLabel, $issue)) {
                $issue[$fieldLabel] = [];
            }

            if ($customField[RedmineCustomFieldColumnEnum::MULTIPLE]) {
                $issue[$fieldLabel][] = $customValue;
            } else {
                $issue[$fieldLabel] = $customValue;
            }

            unset($issue);
        }

        $this->allowUserBindedFieldsHaveAnyUser();

        $redmineAdminUser = UserManager::instance()->getUserById($redmineAdminUserId);
        UserManager::instance()->setCurrentUser($redmineAdminUser);

        $issueToArtifact = [];
        $projectType = EntityTypeEnum::PROJECT();

        $progress = $output->createProgressBar(count($redmineIssues));

        foreach ($redmineIssues as $issueId => $redmineIssue) {
            $redmineProjectId = $redmineIssue[RedmineIssueColumnEnum::PROJECT_ID];
            $tuleapProjectId = $this->refRepo->findTuleapId($projectType, (string) $redmineProjectId);

            $tracker = $this->trackerFactory->getTrackerByShortnameAndProjectId(TransferProjectsCommand::TRACKER_ITEM_NAME, $tuleapProjectId);

            $issueAuthor = $this->userManager->getUserById($this->refRepo->getTuleapUserId($redmineIssue[RedmineIssueColumnEnum::AUTHOR_ID], true));

            // to bypass permission checks
            $issueAuthor = new Tracker_Workflow_WorkflowUser([
                TuleapUserColumnEnum::USER_ID => $issueAuthor->getId(),
                TuleapUserColumnEnum::USER_NAME => $issueAuthor->getUserName(),
                TuleapUserColumnEnum::STATUS => TuleapUserStatusEnum::ACTIVE,
            ]);

            $tuleapArtifact = $this->buildArtifactFields($tracker->id, $redmineIssue);

            $this->enableDirtyHackForSubmittedOn(strtotime($redmineIssue[RedmineIssueColumnEnum::CREATED_ON]));

            // $this->syncProjectMembers($tracker, $tuleapArtifact);

            $artifact = $this->trackerArtifactFactory->createArtifact(
                $tracker,
                $tuleapArtifact,
                $issueAuthor,
                null,
                false,
                false
            );

            $this->disableDirtyHackForSubmittedOn();

            if (!$artifact || !$artifact->id) {
                $output->error(sprintf('Failed to create artifact for issue #%d', $redmineIssue[RedmineIssueColumnEnum::ID]));
                return -1;
            }

            $this->markAsTransfered($issueId, $artifact->id);
            $issueToArtifact[$issueId] = $artifact;

            $progress->advance();
        }

        $this->revertUserBindedFieldsValueFunctions();

        $this->transferParentIds($output, $tracker->id, $redmineIssues, $issueToArtifact);
        return 0;
    }

    private function buildArtifactFields(int $trackerId, array $redmineIssue): array
    {
        $fieldsByLabel = $this->prepareFieldsByLabel($trackerId);

        $redmineColumnNames = array_map('strval', RedmineIssueColumnEnum::values());

        $tuleapArtifact = [];

        foreach ($redmineIssue as $issueField => $issueValue) {
            // Some fields transfer in other ways
            if (
                in_array($issueField, [
                    RedmineIssueColumnEnum::ID,             // markAsTransfered()
                    RedmineIssueColumnEnum::PROJECT_ID,     // $trackerId
                    RedmineIssueColumnEnum::AUTHOR_ID,      // createArtifact() $user argument
                    RedmineIssueColumnEnum::CREATED_ON,     // ... same
                    RedmineIssueColumnEnum::PARENT_ID,      // transferParentIds()
                ])
            ) {
                continue;
            }

            if (empty($fieldsByLabel[$issueField])) {
                throw new Exception(sprintf('Failed to find Tuleap field for Redmine field "%s"', $issueField));
            }

            $tuleapField = $fieldsByLabel[$issueField];
            $isCustomField = ! in_array($issueField, $redmineColumnNames);

            $tuleapArtifact[ $tuleapField[TuleapTrackerFieldColumnEnum::ID] ] = $this->convertValue(
                $issueValue,
                $issueField,
                $isCustomField,
                $this->getTuleapField($tuleapField[TuleapTrackerFieldColumnEnum::ID])
            );
        }

        return $tuleapArtifact;
    }

    private function getTuleapField(int $tuleapFieldId): Tracker_FormElement_Field
    {
        return $this->formElementFactory->getFieldById($tuleapFieldId);
    }

    /**
     * Sometimes we need to convert original value (e.g. for reference values)
     *
     * @param $value
     * @param string $issueField
     * @param bool $isCustomField
     * @param array $tuleapField
     *
     * @return int|mixed|string|null
     *
     * @throws Exception
     */
    private function convertValue(
        $value,
        string $issueField,
        bool $isCustomField,
        Tracker_FormElement_Field $tuleapField
    ) {
        if (empty($value)) {
            return $value;
        }

        switch ($issueField) {
            case RedmineIssueColumnEnum::DESCRIPTION:
                return [
                    'format' => Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT,
                    'content' => $this->textileParser->parse($value),
                ];

            case RedmineIssueColumnEnum::PROJECT_ID:
                return $this->refRepo->getTuleapProjectId($value);

            case RedmineIssueColumnEnum::STATUS_ID:
                $issueStatus = $this->issueStatusRepo->get($value);
                return $this->convertSelectBoxValue($issueStatus[RedmineIssueStatusColumnEnum::NAME], $tuleapField);

            case RedmineIssueColumnEnum::ASSIGNED_TO_ID:
            case RedmineIssueColumnEnum::AUTHOR_ID:
                return $this->refRepo->getTuleapUserId($value, true);

            case RedmineIssueColumnEnum::PRIORITY_ID:
                return $this->convertSelectBoxValue(
                    $this->redmineEnumRepo->getName(RedmineEnumerationTypeEnum::ISSUE_PRIORITY(), $value),
                    $tuleapField
                );

            case RedmineIssueColumnEnum::START_DATE:
            case RedmineIssueColumnEnum::DUE_DATE:
            case RedmineIssueColumnEnum::CREATED_ON:
            case RedmineIssueColumnEnum::UPDATED_ON:
                return $this->convertDate($value);
        }

        // at this point we converted all default fields, so there we return value as it is
        // because we probably don't won't to change it or just don't know how to do it
        if (!$isCustomField) {
            return $value;
        }

        $userField = $this->cfRepo->oneOfName($issueField, RedmineCustomFieldTypeEnum::ISSUE());

        switch ($userField[RedmineCustomFieldColumnEnum::FIELD_FORMAT]) {
            case RedmineCustomFieldFormatEnum::USER:
                return $this->refRepo->getTuleapUserId($value, true);

            case RedmineCustomFieldFormatEnum::LIST:
                return $this->convertSelectBoxValue($value, $tuleapField);

            case RedmineCustomFieldFormatEnum::TEXT:
                return [
                    'format' => Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT,
                    'content' => $value,
                ];

            case RedmineCustomFieldFormatEnum::DATE:
                return $this->convertDate($value);
        }

        return $value;
    }

    private function convertSelectBoxValue($value, Tracker_FormElement_Field $field): int
    {
        if ((!$field instanceof Tracker_FormElement_Field_List)) {
            throw new Exception(
                sprintf(
                    'Failed to convert value "%s" of field "%s": field is expected to be Tracker_FormElement_Field_List',
                    $value,
                    $field->getLabel()
                )
            );
        }

        foreach ($field->getAllValues() as $fieldOption) {
            if ($value === $fieldOption->getLabel()) {
                return $fieldOption->getId();
            }
        }

        throw new Exception(sprintf('Failed to convert value "%s" for field "%s": no match', $value, $field->getLabel()));
    }

    /**
     * @param SymfonyStyle $output
     * @param int $trackerId
     * @param array $redmineIssues
     * @param Tracker_Artifact[] $issueToArtifact
     */
    private function transferParentIds(SymfonyStyle $output, int $trackerId, array $redmineIssues, array $issueToArtifact): void
    {
        $output->section('Transfering parent_id');

        $progress = $output->createProgressBar(count($redmineIssues));

        foreach ($redmineIssues as $issueId => $redmineIssue) {
            $progress->advance();

            if (empty($redmineIssue[RedmineIssueColumnEnum::PARENT_ID])) {
                continue;
            }

            // we can't use $issueToArtifact here because transfer could be run multuple times
            $tuleapParentId = $this->refRepo->getArtifactId($redmineIssue[RedmineIssueColumnEnum::PARENT_ID]);

            $artifact = $issueToArtifact[$issueId];
            $fieldId = $this->getTrackerFieldIdByLabel($artifact->getTrackerId(), RedmineIssueColumnEnum::PARENT_ID);

            $changeset = $artifact->createNewChangeset(
                [$fieldId => ['new_values' => $tuleapParentId]],
                '',
                $artifact->getSubmittedByUser(),
                false
            );

            if (!$changeset || !$changeset->id) {
                throw new Exception(sprintf('Failed to create changeset to set parent_id for issue #%d', $issueId));
            }
        }
    }

    /**
     * @param int $trackerId
     * @return array
     */
    private function prepareFieldsByLabel(int $trackerId): array
    {
        static $trackerFields = [];

        if (!empty($trackerFields[$trackerId])) {
            return $trackerFields[$trackerId];
        }

        // Unfortunately, we have to do it this way, because the object representation doesn't contain formElement_type
        $formFieldRepository = new Tracker_FormElement_FieldDao();
        $fields = iterator_to_array($formFieldRepository->searchByTrackerId($trackerId));
        $trackerFields[$trackerId] = array_combine(
            array_map(
                function (array $field) {
                    return $field[TuleapTrackerFieldColumnEnum::LABEL];
                },
                $fields
            ),
            $fields
        );

        return $trackerFields[$trackerId];
    }

    private function enableDirtyHackForSubmittedOn(int $time): void
    {
        $this->serverRequestTime = $_SERVER['REQUEST_TIME'];
        $_SERVER['REQUEST_TIME'] = $time;
    }

    private function disableDirtyHackForSubmittedOn(): void
    {
        $_SERVER['REQUEST_TIME'] = $this->serverRequestTime;
    }

    private function allowUserBindedFieldsHaveAnyUser(): void
    {
        $tuleapDb = $this->tuleap();

        $backupableFields = $tuleapDb->run('SELECT * FROM ' . TuleapTableEnum::TRACKER_FIELD_LIST_BIND_USERS);

        foreach ($backupableFields as $backupableField) {
            $this->temporaryRebindedUserFieldsRepo->backupField(
                $backupableField[FieldListBindUserColumnEnum::FIELD_ID],
                $backupableField[FieldListBindUserColumnEnum::VALUE_FUNCTION]
            );

            $tuleapDb->update(
                TuleapTableEnum::TRACKER_FIELD_LIST_BIND_USERS,
                [FieldListBindUserColumnEnum::VALUE_FUNCTION => Tracker_FormElement_Field_List_Bind_Users::REGISTERED_USERS_UGROUP_NAME],
                [FieldListBindUserColumnEnum::FIELD_ID => $backupableField[FieldListBindUserColumnEnum::FIELD_ID]]
            );
        }
    }

    private function revertUserBindedFieldsValueFunctions(): void
    {
        $tuleapDb = $this->tuleap();

        foreach ($this->temporaryRebindedUserFieldsRepo->all() as $backupedField) {
            $tuleapDb->update(
                TuleapTableEnum::TRACKER_FIELD_LIST_BIND_USERS,
                [FieldListBindUserColumnEnum::VALUE_FUNCTION => $backupedField[FieldListBindUserColumnEnum::VALUE_FUNCTION]],
                [FieldListBindUserColumnEnum::FIELD_ID => $backupedField[FieldListBindUserColumnEnum::FIELD_ID]]
            );
        }
    }

    /**
     * Add all related users as project members to make bind-fields work
     * They can be removed afterwards
     *
     * @param Tracker $tracker
     *
     * @param array $artifact
     */
    private function syncProjectMembers(Tracker $tracker, array $artifact): void
    {
        $projectId = $tracker->getGroupId();

        if (empty($this->projectMembers[$projectId])) {
            $this->projectMembers[$projectId] = [];
        }

        $artifactUsers = [];
        foreach ($tracker->getFormElementFields() as $formField) {
            if (
                ($formField instanceof Tracker_FormElement_Field_Selectbox
                    || $formField instanceof Tracker_FormElement_Field_MultiSelectbox
                ) && $formField->getBind()->getType() === Tracker_FormElement_Field_List_Bind_Users::TYPE
                && !empty($artifact[$formField->getId()])
            ) {
                $artifactUsers[] = $artifact[$formField->getId()];
            }
        }

        $artifactUsers = array_filter(array_unique($artifactUsers));
        if (!$artifactUsers) {
            return;
        }

        foreach (array_diff($artifactUsers, $this->projectMembers[$projectId]) as $newProjectMemberId) {
            if (!$this->userPermDao->isUserPartOfProjectMembers($projectId, $newProjectMemberId)) {
                $this->userPermDao->addUserAsProjectMember($projectId, $newProjectMemberId);
                $this->projectMembers[$projectId][] = $newProjectMemberId;
            }
        }
    }

    private function convertDate(string $date): string
    {
        if ($date === '0000-00-00') {
            return '';
        }

        return $date;
    }

    private function getTrackerFieldIdByLabel(int $trackerId, string $label): int
    {
        return $this->prepareFieldsByLabel($trackerId)[$label][TuleapTrackerFieldColumnEnum::ID];
    }
}
