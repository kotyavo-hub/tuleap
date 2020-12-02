<?php

namespace Maximaster\Redmine2TuleapPlugin\Command;

use Exception;
use Maximaster\Redmine2TuleapPlugin\Enum\EntityTypeEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\FieldListBindUserColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineAttachmentColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineCustomFieldColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineCustomFieldFormatEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineCustomFieldTypeEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineCustomValueColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineEnumerationTypeEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineIssueColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineIssueStatusColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineTableEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\TuleapProjectColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\TuleapTableEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\TuleapTrackerFieldColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\TuleapUserColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\TuleapUserStatusEnum;
use Maximaster\Redmine2TuleapPlugin\Exception\AttachmentFileNotFoundException;
use Maximaster\Redmine2TuleapPlugin\Framework\GenericTransferCommand;
use Maximaster\Redmine2TuleapPlugin\Repository\PluginRedmine2TuleapReferenceRepository;
use Maximaster\Redmine2TuleapPlugin\Repository\PluginRedmine2TuleapTrackerFieldListBindUsersBackupRepository;
use Maximaster\Redmine2TuleapPlugin\Repository\RedmineCustomFieldRepository;
use Maximaster\Redmine2TuleapPlugin\Repository\RedmineEnumerationRepository;
use Maximaster\Redmine2TuleapPlugin\Repository\RedmineIssueStatusRepository;
use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\EasyStatement;
use Project;
use ReflectionMethod;
use RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tracker_Artifact;
use Tracker_Artifact_ChangesetValue_Text;
use Tracker_Artifact_XMLImport_XMLImportFieldStrategyAttachment;
use Tracker_ArtifactCreator;
use Tracker_ArtifactDao;
use Tracker_ArtifactFactory;
use Tracker_FormElement_Field;
use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_List_Bind_Users;
use Tracker_FormElement_FieldDao;
use Tracker_FormElementFactory;
use Tracker_Workflow_WorkflowUser;
use TrackerFactory;
use Netcarver\Textile;
use Tuleap\Project\UserPermissionsDao;
use Tuleap\Tracker\Artifact\XMLImport\TrackerNoXMLImportLoggedConfig;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use UserManager;

class TransferIssuesCommand extends GenericTransferCommand
{
    const OPTION_LIMIT = 'limit';

    const ATTACHMENTS_FIELD = 'attachments';

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

    /** @var UserManager */
    private $userManager;

    /** @var Tracker_ArtifactCreator */
    private $artifactCreator;

    public static function getDefaultName()
    {
        return 'redmine2tuleap:issues:transfer';
    }

    protected function entityType(): EntityTypeEnum
    {
        return EntityTypeEnum::ISSUE();
    }

    /**
     * TransferIssuesCommand constructor.
     * @param string $pluginDirectory
     * @param EasyDB $redmineDb
     * @param EasyDB $tuleapDb
     * @param PluginRedmine2TuleapReferenceRepository $refRepo
     * @param Tracker_ArtifactFactory $trackerArtifactFactory
     * @param TrackerFactory $trackerFactory
     * @param RedmineCustomFieldRepository $cfRepo
     * @param Tracker_FormElementFactory $formElementFactory
     * @param RedmineIssueStatusRepository $issueStatusRepo
     * @param RedmineEnumerationRepository $redmineEnumRepo
     * @param Textile\Parser $textileParser
     * @param PluginRedmine2TuleapTrackerFieldListBindUsersBackupRepository $temporaryRebindedUserFieldsRepo
     * @param UserPermissionsDao $userPermDao
     * @param UserManager $userManager
     */
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

        $this->artifactCreator = $this->createArtifactCreator($this->trackerArtifactFactory);
    }

    protected function configure()
    {
        parent::configure();

        $this->getDefinition()->addOptions([
            new InputOption(self::OPTION_LIMIT, null, InputOption::VALUE_REQUIRED, 'Limit imported issues number')
        ]);
    }

    protected function transfer(InputInterface $input, SymfonyStyle $output): int
    {
        $redmineDb = $this->redmine();

        $redmineAdminUserId = $this->refRepo->findTuleapId(EntityTypeEnum::USER(), 1);
        if (!$redmineAdminUserId) {
            $output->error('Transfer users firts');
            return -1;
        }

        $transferedProjectIds = $this->refRepo->redmineIdsOfType(EntityTypeEnum::PROJECT());
        if (empty($transferedProjectIds)) {
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

        $condition->andIn(RedmineIssueColumnEnum::PROJECT_ID . ' in (?*)', $transferedProjectIds);

        $issuesQuery .= '
            WHERE ' . $condition->sql() . '
            ORDER BY ' . RedmineIssueColumnEnum::ID . ' ASC
        ';

        if ($limit = $input->getOption(self::OPTION_LIMIT)) {
            $issuesQuery .= ' LIMIT ' . $limit;
        }

        $redmineIssues = $redmineDb->run($issuesQuery, ...$condition->values());

        if (empty($redmineIssues)) {
            $output->note('Nothing to import');
            return 0;
        }

        $output->section(sprintf('Transfering %d issues of %d projects', count($redmineIssues), count($transferedProjectIds)));

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

        $this->loadAttachments($redmineIssues);

        try {
            $this->allowUserBindedFieldsHaveAnyUser();

            $redmineAdminUser = UserManager::instance()->getUserById($redmineAdminUserId);
            UserManager::instance()->setCurrentUser($redmineAdminUser);

            $issueToArtifact = [];
            $progress = $output->createProgressBar(count($redmineIssues));
            $this->transferIssues($progress, $issueToArtifact, $redmineIssues);
        } catch (Exception $e) {
            $output->error($e->getMessage());
            return -1;
        } finally {
            $this->revertUserBindedFieldsValueFunctions();
        }

        // deprecated
        // $this->transferParentIds($output, $redmineIssues, $issueToArtifact);
        return 0;
    }

    private function transferIssues(ProgressBar $progress, array &$issueToArtifact, array $redmineIssues): void
    {
        foreach ($redmineIssues as $issueId => $redmineIssue) {
            // could be alredy created by recursive calls
            if (!empty($issueToArtifact[$issueId])) {
                continue;
            }

            // we should create all parents in the first place
            $parentIssueId = $redmineIssue[RedmineIssueColumnEnum::PARENT_ID];
            if ($parentIssueId && !$this->refRepo->getArtifactId($parentIssueId)) {
                if (empty($redmineIssues[$parentIssueId])) {
                    throw new Exception(
                        sprintf(
                            "Issue #%d has #%d as parent, but we don't import it right now for some reason, so we can't proceed",
                            $issueId,
                            $parentIssueId
                        )
                    );
                }

                $this->transferIssues($progress, $issueToArtifact, [
                    $parentIssueId => $redmineIssues[$parentIssueId],
                ]);
            }

            $artifact = $this->createArtifact($redmineIssue);
            $this->markAsTransfered($issueId, $artifact->id);
            $issueToArtifact[$issueId] = $artifact;

            $progress->advance();
        }
    }

    private function createArtifact($redmineIssue): Tracker_Artifact
    {
        $projectType = EntityTypeEnum::PROJECT();

        $issueId = $redmineIssue[RedmineIssueColumnEnum::ID];

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

        $submittedOnTimestamp = strtotime($redmineIssue[RedmineIssueColumnEnum::CREATED_ON]);

        $artifact = null;

        try {
            // we have to bypass "project should be active" rule is such a way. See ArtifactLinkValidator::isValid
            $artifactProject = $tracker->getProject();
            $projectStatus = $artifactProject->data_array[TuleapProjectColumnEnum::STATUS];
            $artifactProject->data_array[TuleapProjectColumnEnum::STATUS] = Project::STATUS_ACTIVE;

            if ($this->config()->createArtifactIdFromIssueId()) {
                $artifact = $this->artifactCreator->createBareWithAllData(
                    $tracker,
                    $issueId,
                    $submittedOnTimestamp,
                    $issueAuthor
                );

                if (!$artifact) {
                    throw new RuntimeException(sprintf('Failed to create artifact for issue #%d', $issueId));
                }

                $changeset = $this->artifactCreator->createFirstChangeset(
                    $artifact,
                    $tuleapArtifact,
                    $issueAuthor,
                    $submittedOnTimestamp,
                    false,
                    new CreatedFileURLMapping(),
                    new TrackerNoXMLImportLoggedConfig()
                );

                if (!$changeset) {
                    throw new RuntimeException(sprintf('Failed to create artifact changeset for issue #%d', $issueId));
                }
            } else {
                try {
                    $this->enableDirtyHackForSubmittedOn($submittedOnTimestamp);

                    $artifact = $this->trackerArtifactFactory->createArtifact(
                        $tracker,
                        $tuleapArtifact,
                        $issueAuthor,
                        null,
                        false,
                        false
                    );
                } finally {
                    $this->disableDirtyHackForSubmittedOn();
                }
            }
        } finally {
            $artifactProject->data_array[TuleapProjectColumnEnum::STATUS] = $projectStatus;
        }

        if (!$artifact || !$artifact->id) {
            throw new Exception(sprintf('Failed to create artifact for issue #%d', $redmineIssue[RedmineIssueColumnEnum::ID]));
        }

        return $artifact;
    }

    private function buildArtifactFields(int $trackerId, array $redmineIssue): array
    {
        $fieldsByLabel = $this->prepareFieldsByLabel($trackerId);

        $redmineColumnNames = array_map('strval', RedmineIssueColumnEnum::values());

        static $ignoredFields;
        if ($ignoredFields === null) {
            $ignoredFields = [
                RedmineIssueColumnEnum::ID,             // markAsTransfered()
                RedmineIssueColumnEnum::PROJECT_ID,     // $trackerId
                RedmineIssueColumnEnum::AUTHOR_ID,      // createArtifact() $user argument
                RedmineIssueColumnEnum::CREATED_ON,     // ... same
            ];

            // if (!$createArtifactIdFromIssueId) {
            //     $ignoredFields[] = RedmineIssueColumnEnum::PARENT_ID; // transferParentIds()
            // }
        }

        $tuleapArtifact = [];

        foreach ($redmineIssue as $issueField => $issueValue) {
            // Some fields transfer in other ways
            if (in_array($issueField, $ignoredFields)) {
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

            case RedmineIssueColumnEnum::PARENT_ID:
                return ['new_values' => $this->refRepo->getArtifactId($value)];

            case self::ATTACHMENTS_FIELD:
                return $this->convertAttachments($value);
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
     * @param array $redmineIssues
     * @param Tracker_Artifact[] $issueToArtifact
     */
    private function transferParentIds(SymfonyStyle $output, array $redmineIssues, array $issueToArtifact): void
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

            // we have to bypass "project should be active" rule is such a way. See ArtifactLinkValidator::isValid
            $artifactProject = $artifact->getTracker()->getProject();
            $projectStatus = $artifactProject->data_array[TuleapProjectColumnEnum::STATUS];
            $artifactProject->data_array[TuleapProjectColumnEnum::STATUS] = Project::STATUS_ACTIVE;

            try {
                $changeset = $artifact->createNewChangeset(
                    [$fieldId => ['new_values' => $tuleapParentId]],
                    '',
                    $artifact->getSubmittedByUser(),
                    false
                );
            } finally {
                $artifactProject->data_array[TuleapProjectColumnEnum::STATUS] = $projectStatus;
            }

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
        $trackerFields[$trackerId] = array_combine(array_column($fields, TuleapTrackerFieldColumnEnum::LABEL), $fields);

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

    private function loadAttachments(array &$redmineIssues): void
    {
        if (!$redmineIssues) {
            return;
        }

        $redmineDb = $this->redmine();

        $attachmentQueryCondition = EasyStatement::open()
            ->with(RedmineAttachmentColumnEnum::CONTAINER_TYPE . ' = ?', 'Issue')
            ->andWith(RedmineAttachmentColumnEnum::DISK_FILENAME . ' != ""')
            ->andIn(RedmineAttachmentColumnEnum::CONTAINER_ID . ' in (?*)', array_column($redmineIssues, RedmineIssueColumnEnum::ID));

        $redmineAttachments = $redmineDb->run(
            '
                SELECT ' . implode(', ' , [
                    RedmineAttachmentColumnEnum::ID,
                    RedmineAttachmentColumnEnum::CONTAINER_ID,
                    RedmineAttachmentColumnEnum::CONTENT_TYPE,
                    RedmineAttachmentColumnEnum::FILENAME,
                    RedmineAttachmentColumnEnum::FILESIZE,
                    RedmineAttachmentColumnEnum::DISK_FILENAME,
                    RedmineAttachmentColumnEnum::DISK_DIRECTORY,
                    RedmineAttachmentColumnEnum::DESCRIPTION,
                ]) . '
                FROM ' . RedmineTableEnum::ATTACHMENTS . '
                WHERE ' . $attachmentQueryCondition->sql() . '
            ',
            ...$attachmentQueryCondition->values()
        );

        foreach ($redmineAttachments as $redmineAttachment) {
            $issueId = $redmineAttachment[RedmineAttachmentColumnEnum::CONTAINER_ID];
            if (empty($redmineIssues[$issueId])) {
                continue;
            }

            $issue =& $redmineIssues[$issueId];

            if (empty($issue[self::ATTACHMENTS_FIELD])) {
                $issue[self::ATTACHMENTS_FIELD] = [];
            }

            $issue[self::ATTACHMENTS_FIELD][] = $redmineAttachment;

            unset($issue);
        }
    }

    private function convertAttachments(array $redmineAttachments): array
    {
        $attachments = [];
        foreach ($redmineAttachments as $redmineAttachment) {
            try {
                $attachments[] = $this->convertAttachment($redmineAttachment);
            } catch (AttachmentFileNotFoundException $e) {
                // it's possible to have broken files on redmine instance, so just ignore such files
                continue;
            }
        }

        return $attachments;
    }

    private function convertAttachment(array $redmineAttachment): array
    {
        static $redmineFilesDirectory;
        if ($redmineFilesDirectory === null) {
            $redmineFilesDirectory = implode(DIRECTORY_SEPARATOR, [
                $this->config()->redmineDirectory(),
                'files'
            ]);
        }

        $filePath = implode(DIRECTORY_SEPARATOR, [
            $redmineFilesDirectory,
            $redmineAttachment[RedmineAttachmentColumnEnum::DISK_DIRECTORY],
            $redmineAttachment[RedmineAttachmentColumnEnum::DISK_FILENAME],
        ]);

        if (!file_exists($filePath)) {
            throw new AttachmentFileNotFoundException(sprintf('File not found: %s', $filePath));
        }

        return [
            // special mark to copy files instead of their moving
            Tracker_Artifact_XMLImport_XMLImportFieldStrategyAttachment::FILE_INFO_COPY_OPTION => 1,

            'name' => $redmineAttachment[RedmineAttachmentColumnEnum::FILENAME],
            'description' => $redmineAttachment[RedmineAttachmentColumnEnum::DESCRIPTION] ?: '',
            'type' => $redmineAttachment[RedmineAttachmentColumnEnum::CONTENT_TYPE] ?: mime_content_type($filePath),
            'tmp_name' => $filePath,
            'size' => $redmineAttachment[RedmineAttachmentColumnEnum::FILESIZE],
            'error' => 0,
        ];
    }

    private function createArtifactCreator(Tracker_ArtifactFactory $trackerArtifactFactory): Tracker_ArtifactCreator
    {
        $factoryMethod = (new ReflectionMethod($trackerArtifactFactory, 'getArtifactCreator'));
        $factoryMethod->setAccessible(true);
        return $factoryMethod->invoke($trackerArtifactFactory);
    }
}
