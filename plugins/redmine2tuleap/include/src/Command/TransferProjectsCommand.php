<?php

namespace Maximaster\Redmine2TuleapPlugin\Command;

use Exception;
use Maximaster\Redmine2TuleapPlugin\Enum\EntityTypeEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineCustomFieldColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineCustomFieldFormatEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineCustomValueColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineIssueColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineProjectColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineProjectStatusEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineTableEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\TuleapProjectColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\TuleapProjectExtraFieldColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\TuleapProjectExtraFieldTypeEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\TuleapProjectExtraValueColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\TuleapProjectTypeEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\TuleapServiceColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\TuleapTableEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\TuleapTrackerFieldColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\TuleapTrackerFormElementTypeEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\TuleapTrackerStringFieldColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Framework\GenericTransferCommand;
use Maximaster\Redmine2TuleapPlugin\Repository\PluginRedmine2TuleapReferenceRepository;
use Maximaster\Redmine2TuleapPlugin\Repository\RedmineCustomFieldRepository;
use Maximaster\Redmine2TuleapPlugin\Repository\RedmineIssueStatusRepository;
use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\EasyStatement;
use Project;
use ProjectManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tracker;
use Tracker_FormElement_Field_Date;
use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_List_Bind_Static;
use Tracker_FormElement_Field_List_Bind_Users;
use Tracker_FormElementFactory;
use Tracker_Semantic_Contributor;
use Tracker_Semantic_Description;
use Tracker_Semantic_DescriptionDao;
use Tracker_Semantic_Status;
use Tracker_Semantic_StatusDao;
use Tracker_Semantic_Title;
use Tracker_Semantic_TitleDao;
use Tracker_SemanticManager;
use Tracker_Tooltip;
use Tracker_TooltipDao;
use TrackerFactory;
use Tuleap\Tracker\Creation\TrackerCreationSettings;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao;
use Tuleap\Tracker\TrackerColor;
use Tracker_FormElement_Field;

class TransferProjectsCommand extends GenericTransferCommand
{
    public const SPECIFIC_PROPERTIES = 'specific_properties';

    public const PROJECT_STATUS_CONVERSION = [
        RedmineProjectStatusEnum::OPENED => Project::STATUS_ACTIVE,
        RedmineProjectStatusEnum::CLOSED => Project::STATUS_SUSPENDED,
        RedmineProjectStatusEnum::ARCHIVED => Project::STATUS_DELETED,
    ];

    public const DEFAULT_PROJECT_STATUS = Project::STATUS_PENDING;

    public const PROJECT_EXTRA_FIELD_CONVERSION = [
        RedmineCustomFieldFormatEnum::STRING => TuleapProjectExtraFieldTypeEnum::LINE,
        RedmineCustomFieldFormatEnum::TEXT => TuleapProjectExtraFieldTypeEnum::TEXT,
    ];

    public const DEFAULT_PROJECT_EXTRA_FIELD_TYPE = TuleapProjectExtraFieldTypeEnum::LINE;

    public const TEMPLATE_PROJECT_ID = 100;

    public const ISSUE_CUSTOM_FIELD_FIELD_FORMAT_CONVERSION = [
        RedmineCustomFieldFormatEnum::DATE => TuleapTrackerFormElementTypeEnum::DATE,
        RedmineCustomFieldFormatEnum::TEXT => TuleapTrackerFormElementTypeEnum::TEXT,
        RedmineCustomFieldFormatEnum::BOOL => TuleapTrackerFormElementTypeEnum::INT,
        RedmineCustomFieldFormatEnum::USER => TuleapTrackerFormElementTypeEnum::BIND_USERS,
        RedmineCustomFieldFormatEnum::FLOAT => TuleapTrackerFormElementTypeEnum::FLOAT,
        RedmineCustomFieldFormatEnum::LIST => TuleapTrackerFormElementTypeEnum::SELECT_BOX,
        RedmineCustomFieldFormatEnum::ENUMERATION => [
            TuleapTrackerFormElementTypeEnum::SELECT_BOX,
            TuleapTrackerFormElementTypeEnum::MULTI_SELECT_BOX,
        ],
        RedmineCustomFieldFormatEnum::LINK => TuleapTrackerFormElementTypeEnum::STRING,
        RedmineCustomFieldFormatEnum::STRING => TuleapTrackerFormElementTypeEnum::STRING,
        RedmineCustomFieldFormatEnum::INT => TuleapTrackerFormElementTypeEnum::INT,
    ];

    /** @var RedmineCustomFieldRepository */
    private $cfRepo;

    /** @var RedmineIssueStatusRepository */
    private $issueStatusRepo;

    /** @var ProjectManager */
    private $projectManager;

    /** @var TrackerFactory */
    private $trackerFactory;

    /** @var Tracker_FormElementFactory */
    private $trackerFormElementFactory;

    public static function getDefaultName()
    {
        return 'redmine2tuleap:projects:transfer';
    }

    protected function entityType(): EntityTypeEnum
    {
        return EntityTypeEnum::PROJECT();
    }

    public function __construct(
        EasyDB $redmineDb,
        EasyDB $tuleapDb,
        PluginRedmine2TuleapReferenceRepository $refRepo,
        RedmineCustomFieldRepository $cfRepo,
        RedmineIssueStatusRepository $issueStatusRepo,
        ProjectManager $projectManager,
        TrackerFactory $trackerFactory,
        Tracker_FormElementFactory $trackerFormElementFactory
    )
    {
        parent::__construct($redmineDb, $tuleapDb, $refRepo);
        $this->cfRepo = $cfRepo;
        $this->issueStatusRepo = $issueStatusRepo;
        $this->projectManager = $projectManager;
        $this->trackerFactory = $trackerFactory;
        $this->trackerFormElementFactory = $trackerFormElementFactory;
    }

    protected function transfer(InputInterface $input, SymfonyStyle $output): int
    {
        $redmineDb = $this->redmine();
        $tuleapDb = $this->tuleap();

        $redmineProjectCustomFields = $this->cfRepo->allOfProject(RedmineCustomFieldColumnEnum::ID);
        $redmineProjectCustomFieldsCount = count($redmineProjectCustomFields);

        $output->note(sprintf('Trying to create %d custom field%s', $redmineProjectCustomFieldsCount, $redmineProjectCustomFieldsCount > 1 ? 's' : ''));

        $redmineProjectExtraFieldToTuleap = [];
        foreach ($redmineProjectCustomFields as $redmineProjectCustomField) {
            $redmineExtraFieldId = $redmineProjectCustomField[RedmineCustomFieldColumnEnum::ID];

            $tuleapProjectExtraField = [
                TuleapProjectExtraFieldColumnEnum::DESC_REQUIRED => $redmineProjectCustomField[RedmineCustomFieldColumnEnum::IS_REQUIRED],
                TuleapProjectExtraFieldColumnEnum::DESC_NAME => $redmineProjectCustomField[RedmineCustomFieldColumnEnum::NAME],
                TuleapProjectExtraFieldColumnEnum::DESC_DESCRIPTION => $redmineProjectCustomField[RedmineCustomFieldColumnEnum::DESCRIPTION],
                TuleapProjectExtraFieldColumnEnum::DESC_RANK => $redmineProjectCustomField[RedmineCustomFieldColumnEnum::POSITION],
                TuleapProjectExtraFieldColumnEnum::DESC_TYPE => $this->convertExtraFieldType(new RedmineCustomFieldFormatEnum($redmineProjectCustomField[RedmineCustomFieldColumnEnum::FIELD_FORMAT])),
            ];

            $tuleapFieldId = $tuleapDb->single(
                '
                    SELECT ' . TuleapProjectExtraFieldColumnEnum::GROUP_DESC_ID . '
                    FROM ' . TuleapTableEnum::PROJECT_EXTRA_FIELD . '
                    WHERE ' . TuleapProjectExtraFieldColumnEnum::DESC_NAME . ' = ?
                ',
                [ $tuleapProjectExtraField[TuleapProjectExtraFieldColumnEnum::DESC_NAME] ]
            );

            if (!$tuleapFieldId) {
                $tuleapFieldId = $tuleapDb->insertGet(TuleapTableEnum::PROJECT_EXTRA_FIELD, $tuleapProjectExtraField);

                if (!$tuleapFieldId) {
                    $output->error(
                        sprintf(
                            'Failed to create extra project field [%d] "%s": %d %d %s',
                            $redmineExtraFieldId,
                            $redmineProjectCustomField[RedmineCustomFieldColumnEnum::NAME],
                            ...$tuleapDb->errorInfo()
                        ));
                    return -1;
                }

                $tuleapFieldId = $tuleapDb->lastInsertId();
            }

            $redmineProjectExtraFieldToTuleap[$redmineExtraFieldId] = $tuleapFieldId;
        }

        $projectSelect = preg_replace('/^/', RedmineTableEnum::PROJECTS . '.', [
            RedmineProjectColumnEnum::ID,
            RedmineProjectColumnEnum::NAME,
            RedmineProjectColumnEnum::DESCRIPTION,
            RedmineProjectColumnEnum::HOMEPAGE,
            RedmineProjectColumnEnum::IS_PUBLIC,
            RedmineProjectColumnEnum::PARENT_ID,
            RedmineProjectColumnEnum::CREATED_ON,
            RedmineProjectColumnEnum::UPDATED_ON,
            RedmineProjectColumnEnum::IDENTIFIER,
            RedmineProjectColumnEnum::STATUS,
            RedmineProjectColumnEnum::LFT,
            RedmineProjectColumnEnum::RGT,
            RedmineProjectColumnEnum::INHERIT_MEMBERS,
            RedmineProjectColumnEnum::DEFAULT_ASSIGNEE_ID,
        ]);

        $projectSelectQuery = ' FROM ' . RedmineTableEnum::PROJECTS;

        $queryValues = [];
        foreach ($redmineProjectExtraFieldToTuleap as $redmineProjectCustomFieldId => $tuleapProjectExtraFieldId) {
            $extraFieldRowAlias = $this->getProjectExtraFieldRowAlias($tuleapProjectExtraFieldId);

            $statement = EasyStatement::open()
                ->with("{$extraFieldRowAlias}." . RedmineCustomValueColumnEnum::CUSTOM_FIELD_ID . ' = ?', $redmineProjectCustomFieldId)
                ->andWith("{$extraFieldRowAlias}." . RedmineCustomValueColumnEnum::CUSTOMIZED_ID . ' = ' . RedmineTableEnum::PROJECTS . '.' . RedmineProjectColumnEnum::ID);

            $projectSelectQuery .= ' LEFT JOIN ' . RedmineTableEnum::CUSTOM_VALUES . " {$extraFieldRowAlias} ON " .
                $statement->sql();

            $queryValues = array_merge($queryValues, $statement->values());

            $projectSelect[] = sprintf(
                '%s.%s as %s',
                $extraFieldRowAlias,
                RedmineCustomValueColumnEnum::VALUE,
                $this->getProjectExtraFieldValueAlias($tuleapProjectExtraFieldId)
            );
        }

        if ($alreadyCreatedProjectIds = $this->transferedRedmineIdList()) {
            $projectSelectQuery .= ' WHERE ' . EasyStatement::open()->in(
                RedmineTableEnum::PROJECTS . '.' .RedmineProjectColumnEnum::ID . ' not in (?*)',
                $alreadyCreatedProjectIds
            );
            $queryValues = array_merge($queryValues, $alreadyCreatedProjectIds);
        }

        $projectSelectQuery .= ' GROUP BY ' . RedmineTableEnum::PROJECTS . '.' .RedmineProjectColumnEnum::ID .
            ' ORDER BY ' . RedmineTableEnum::PROJECTS . '.' . RedmineProjectColumnEnum::ID . ' ASC';

        $projectSelectQuery = 'SELECT ' . implode(', ', $projectSelect) . ' ' . $projectSelectQuery;

        $redmineProjects = $redmineDb->run($projectSelectQuery, ...$queryValues);

        if (!$redmineProjects) {
            $output->note('Nothing to import. Exit');
            return 0;
        }

        $redmineProjectCount = count($redmineProjects);
        $output->note(sprintf('Going to import %d project%s', $redmineProjectCount, $redmineProjectCount > 1));

        $progress = $output->createProgressBar($redmineProjectCount);

        $projectType = EntityTypeEnum::PROJECT();

        $trackerTemplate = null;
        foreach ($redmineProjects as $redmineProject) {
            try {
                $tuleapProject = [
                    TuleapProjectColumnEnum::GROUP_NAME => $redmineProject[RedmineProjectColumnEnum::NAME],
                    TuleapProjectColumnEnum::ACCESS => $redmineProject[RedmineProjectColumnEnum::IS_PUBLIC] ? Project::ACCESS_PUBLIC : Project::ACCESS_PRIVATE,
                    TuleapProjectColumnEnum::STATUS => $this->converStatus($redmineProject[RedmineProjectColumnEnum::STATUS]),
                    TuleapProjectColumnEnum::UNIX_GROUP_NAME => $redmineProject[RedmineProjectColumnEnum::IDENTIFIER],
                    TuleapProjectColumnEnum::HTTP_DOMAIN => sprintf('%s._DOMAIN_NAME_', $redmineProject[RedmineProjectColumnEnum::IDENTIFIER]),
                    TuleapProjectColumnEnum::SHORT_DESCRIPTION => $redmineProject[RedmineProjectColumnEnum::DESCRIPTION] ?? '',
                    TuleapProjectColumnEnum::TYPE => TuleapProjectTypeEnum::PROJECT,
                ];

                $projectAdded = $tuleapDb->insert(TuleapTableEnum::PROJECTS, $tuleapProject);

                if (!$projectAdded) {
                    $output->error(sprintf('Failed to create project "%s": %d %d %s', $redmineProject[RedmineProjectColumnEnum::ID], ...$tuleapDb->errorInfo()));
                    return -1;
                }

                $tuleapProjectId = $tuleapProject[TuleapProjectColumnEnum::GROUP_ID] = $tuleapDb->lastInsertId();

                foreach ($redmineProjectExtraFieldToTuleap as $redmineCustomFieldId => $tuleapProjectExtraFieldId) {
                    $fieldKey = $this->getProjectExtraFieldValueAlias($tuleapProjectExtraFieldId);

                    if (empty($redmineProject[$fieldKey])) {
                        continue;
                    }

                    $tuleapDb->insert(TuleapTableEnum::PROJECT_EXTRA_VALUE, [
                        TuleapProjectExtraValueColumnEnum::GROUP_ID => $tuleapProjectId,
                        TuleapProjectExtraValueColumnEnum::GROUP_DESC_ID => $tuleapProjectExtraFieldId,
                        TuleapProjectExtraValueColumnEnum::VALUE => $redmineProject[$fieldKey],
                    ]);
                }

                $this->configureProjectServices($tuleapProject);

                if ($trackerTemplate === null) {
                    $trackerTemplate = $this->createTrackerTemplate($tuleapProjectId);
                } else {
                    $this->trackerFactory->duplicate($trackerTemplate->getGroupId(), $tuleapProjectId, null);
                }

                $this->markAsTransfered($projectType, (string) $redmineProject[RedmineProjectColumnEnum::ID], (string) $tuleapProjectId);
            } catch (Exception $e) {
                $output->error($e->getMessage());
                return -1;
            }

            $progress->advance();
        }

        return 0;
    }

    private function converStatus(int $redmineStatus): string
    {
        return self::PROJECT_STATUS_CONVERSION[$redmineStatus] ?? self::DEFAULT_PROJECT_STATUS;
    }

    private function convertExtraFieldType(RedmineCustomFieldFormatEnum $customFieldFormat): string
    {
        $customFieldFormatValue = $customFieldFormat->getValue();

        return self::PROJECT_EXTRA_FIELD_CONVERSION[$customFieldFormatValue] ?? self::DEFAULT_PROJECT_EXTRA_FIELD_TYPE;
    }

    private function getProjectExtraFieldRowAlias(int $extraFieldId): string
    {
        return "extra_{$extraFieldId}";
    }

    private function getProjectExtraFieldValueAlias(int $extraFieldId): string
    {
        return $this->getProjectExtraFieldRowAlias($extraFieldId) . '_value';
    }

    /**
     * Let's copy allowed project services from template project
     * Otherwise there won't be any service to use
     *
     * @param array $tuleapProject
     *
     * @return void
     *
     * @throws Exception
     */
    private function configureProjectServices(array $tuleapProject): void
    {
        $tuleapDb = $this->tuleap();

        /** @var array $defaultServices */
        static $defaultServices = null;
        if ($defaultServices === null) {
            $defaultServices = $tuleapDb->run('
                SELECT ' . implode(', ',
                    // all except PRIMARY and project id
                    array_diff(
                        array_map('strval', TuleapServiceColumnEnum::values()),
                        [TuleapServiceColumnEnum::SERVICE_ID, TuleapServiceColumnEnum::GROUP_ID]
                    )
                ) . '
                FROM ' . TuleapTableEnum::SERVICE . '
                WHERE ' . TuleapServiceColumnEnum::GROUP_ID . ' = ?
            ', self::TEMPLATE_PROJECT_ID);

            if (!is_array($defaultServices)) {
                throw new Exception(sprintf("Failed to fetch default services: %d %d %s", ...$tuleapDb->errorInfo()));
            }
        }

        foreach ($defaultServices as $defaultService) {
            $inserted = $tuleapDb->insert(TuleapTableEnum::SERVICE, [
                TuleapServiceColumnEnum::GROUP_ID => $tuleapProject[TuleapProjectColumnEnum::GROUP_ID],
                TuleapServiceColumnEnum::LINK => $this->prepareServiceLink($defaultService[TuleapServiceColumnEnum::LINK], $tuleapProject)
            ] + $defaultService);

            if (!$inserted) {
                throw new Exception(
                    sprintf(
                        "Failed to insert default service to tuleap project '%s': %d %d %s",
                        $tuleapProject[TuleapProjectColumnEnum::GROUP_NAME],
                        ...$tuleapDb->errorInfo()
                    )
                );
            }
        }
    }

    private function prepareServiceLink(string $link, array $tuleapProject): string
    {
        $replaces = [
            '$group_id' => $tuleapProject[TuleapProjectColumnEnum::GROUP_ID],
        ];

        return str_replace(array_keys($replaces), $replaces, $link);
    }

    /**
     * @param int $tuleapProjectId
     *
     * @return Tracker
     *
     * @throws Exception
     */
    private function createTrackerTemplate(int $tuleapProjectId): Tracker
    {
        $trackerTemplate = new Tracker(
            null,
            $tuleapProjectId,
            'Issues',
            '',
            'Issue',
            true,
            '',
            '',
            '',
            null,
            1,
            0,
            0,
            TrackerColor::default(),
            true
        );

        try {
            $trackerId = $this->trackerFactory->saveObject($trackerTemplate, new TrackerCreationSettings(true));
            if (!$trackerId) {
                throw new Exception('Unknown reason');
            }

            $trackerTemplate = $this->trackerFactory->getTrackerById($trackerId);
            $this->addDefaultTrackerFields($trackerTemplate);
        } catch (Exception $e) {
            throw new Exception(sprintf('Failed to create tracker template: %s', $e->getMessage()));
        }
        return $trackerTemplate;
    }

    /**
     * @param Tracker $tracker
     *
     * @throws Exception
     */
    private function addDefaultTrackerFields(Tracker $tracker): void
    {
        $fields = [
            [
                TuleapTrackerFieldColumnEnum::FORMELEMENT_TYPE => TuleapTrackerFormElementTypeEnum::ARTIFACT_ID,
                TuleapTrackerFieldColumnEnum::LABEL => RedmineIssueColumnEnum::ID,
            ],
            [
                TuleapTrackerFieldColumnEnum::FORMELEMENT_TYPE => TuleapTrackerFormElementTypeEnum::STRING,
                TuleapTrackerFieldColumnEnum::LABEL => RedmineIssueColumnEnum::SUBJECT,
                TuleapTrackerFieldColumnEnum::REQUIRED => true,
                self::SPECIFIC_PROPERTIES => [
                    TuleapTrackerStringFieldColumnEnum::MAXCHARS => 255,
                ],
            ],
            [
                TuleapTrackerFieldColumnEnum::FORMELEMENT_TYPE => TuleapTrackerFormElementTypeEnum::TEXT,
                TuleapTrackerFieldColumnEnum::LABEL => RedmineIssueColumnEnum::DESCRIPTION,
            ],
            [
                TuleapTrackerFieldColumnEnum::FORMELEMENT_TYPE => TuleapTrackerFormElementTypeEnum::SELECT_BOX,
                TuleapTrackerFieldColumnEnum::LABEL => RedmineIssueColumnEnum::STATUS_ID,
                TuleapTrackerFieldColumnEnum::REQUIRED => true,
                'bind-type' => Tracker_FormElement_Field_List_Bind_Static::TYPE,
                'bind' => ['add' => implode("\n", $this->issueStatusRepo->allLabels())],
            ],
            [
                TuleapTrackerFieldColumnEnum::FORMELEMENT_TYPE => TuleapTrackerFormElementTypeEnum::INT,
                TuleapTrackerFieldColumnEnum::LABEL => 'priority',
            ],
            [
                TuleapTrackerFieldColumnEnum::FORMELEMENT_TYPE => TuleapTrackerFormElementTypeEnum::SELECT_BOX,
                TuleapTrackerFieldColumnEnum::LABEL => RedmineIssueColumnEnum::ASSIGNED_TO_ID,
                'bind-type' => Tracker_FormElement_Field_List_Bind_Users::TYPE,
                'bind' => ['value_function' => ['', 'group_members']],
            ],
            [
                TuleapTrackerFieldColumnEnum::FORMELEMENT_TYPE => TuleapTrackerFormElementTypeEnum::ARTIFACT_LINK,
                TuleapTrackerFieldColumnEnum::LABEL => RedmineIssueColumnEnum::PARENT_ID,
            ],
            [
                TuleapTrackerFieldColumnEnum::FORMELEMENT_TYPE => TuleapTrackerFormElementTypeEnum::DATE,
                TuleapTrackerFieldColumnEnum::LABEL => RedmineIssueColumnEnum::START_DATE,
                self::SPECIFIC_PROPERTIES => [
                    'default_value_type' => Tracker_FormElement_Field_Date::DEFAULT_VALUE_TYPE_REALDATE,
                    'default_value' => '',
                ],
            ],
            [
                TuleapTrackerFieldColumnEnum::FORMELEMENT_TYPE => TuleapTrackerFormElementTypeEnum::DATE,
                TuleapTrackerFieldColumnEnum::LABEL => RedmineIssueColumnEnum::DUE_DATE,
                self::SPECIFIC_PROPERTIES => [
                    'default_value_type' => Tracker_FormElement_Field_Date::DEFAULT_VALUE_TYPE_REALDATE,
                    'default_value' => '',
                ],
            ],
            [
                TuleapTrackerFieldColumnEnum::FORMELEMENT_TYPE => TuleapTrackerFormElementTypeEnum::INT,
                TuleapTrackerFieldColumnEnum::LABEL => RedmineIssueColumnEnum::ESTIMATED_HOURS,
            ],
        ];

        foreach ($this->cfRepo->allOfIssue(RedmineCustomFieldColumnEnum::ID) as $customFieldId => $customField) {
            $field = [
                TuleapTrackerFieldColumnEnum::FORMELEMENT_TYPE => $this->getCustomFieldFormElementType($customField),
                TuleapTrackerFieldColumnEnum::LABEL => $customField[RedmineCustomFieldColumnEnum::NAME],
            ];

            switch ($field[TuleapTrackerFieldColumnEnum::FORMELEMENT_TYPE]) {
                case Tracker_FormElement_Field_List_Bind_Users::TYPE:
                    $field = [
                        TuleapTrackerFieldColumnEnum::FORMELEMENT_TYPE => TuleapTrackerFormElementTypeEnum::SELECT_BOX,
                        'bind-type' => Tracker_FormElement_Field_List_Bind_Users::TYPE,
                        'bind' => ['value_function' => ['', 'group_members']],
                    ] + $field;
                    break;

                case TuleapTrackerFormElementTypeEnum::SELECT_BOX:
                    $field += [
                        'bind-type' => Tracker_FormElement_Field_List_Bind_Static::TYPE,
                        'bind' => ['add' => implode("\n", $this->cfRepo->valuesOfField($customFieldId))],
                    ];
                    break;

                case TuleapTrackerFormElementTypeEnum::DATE:
                    $field += [
                        self::SPECIFIC_PROPERTIES => [
                            'default_value_type' => Tracker_FormElement_Field_Date::DEFAULT_VALUE_TYPE_REALDATE,
                            'default_value' => '',
                        ],
                    ];
                    break;
            }

            if ($customField[RedmineCustomFieldColumnEnum::MULTIPLE]) {
                switch ($field[TuleapTrackerFieldColumnEnum::FORMELEMENT_TYPE]) {
                    case TuleapTrackerFormElementTypeEnum::SELECT_BOX:
                        $field[TuleapTrackerFieldColumnEnum::FORMELEMENT_TYPE] = TuleapTrackerFormElementTypeEnum::MULTI_SELECT_BOX;
                        break;
                }
            }

            $fields[] = $field;
        }

        foreach ($fields as $fieldRank => $field) {
            $field += [
                TuleapTrackerFieldColumnEnum::RANK => $fieldRank + 1,
                TuleapTrackerFieldColumnEnum::USE_IT => 1,
            ];

            $fieldId = $this->trackerFormElementFactory->createFormElement(
                $tracker,
                $field[TuleapTrackerFieldColumnEnum::FORMELEMENT_TYPE],
                $field,
                true,
                true
            );

            if (!$fieldId) {
                global $Response;
                throw new Exception(
                    sprintf(
                        'Failed to create form element %s: %s',
                        $field[TuleapTrackerFieldColumnEnum::LABEL],
                        $Response->_feedback->fetchAsPlainText()
                    )
                );
            }
        }

        $this->configureSemantics($tracker);
    }

    /**
     * @param array $customField
     *
     * @return string
     *
     * @throws Exception
     */
    private function getCustomFieldFormElementType(array $customField): string
    {
        $customFieldFormat = $customField[RedmineCustomFieldColumnEnum::FIELD_FORMAT];
        if (empty(self::ISSUE_CUSTOM_FIELD_FIELD_FORMAT_CONVERSION[$customFieldFormat])) {
            throw new Exception(sprintf('Unsupported field format: %s', $customFieldFormat));
        }

        $isMultiple = (bool) $customField[RedmineCustomFieldColumnEnum::MULTIPLE];

        $tuleapFieldFormat = self::ISSUE_CUSTOM_FIELD_FIELD_FORMAT_CONVERSION[$customFieldFormat];
        return is_array($tuleapFieldFormat) ? $tuleapFieldFormat[(int) $isMultiple] : $tuleapFieldFormat;
    }

    /**
     * @param Tracker $tracker
     *
     * @throws Exception
     */
    private function configureSemantics(Tracker $tracker): void
    {
        $trackerFields = $tracker->getFormElementFields();

        $trackerFieldLabels = array_map(
            function (Tracker_FormElement_Field $trackerField) {
                return $trackerField->getLabel();
            },
            $trackerFields
        );

        /** @var Tracker_FormElement_Field[] $trackerFields */
        $trackerFields = array_combine($trackerFieldLabels, $trackerFields);

        $trackerFieldIds = array_combine(
            $trackerFieldLabels,
            array_map(
                function (Tracker_FormElement_Field $trackerField) {
                    return $trackerField->getId();
                },
                $trackerFields
            )
        );

        static $openedStatusLabels;
        if ($openedStatusLabels === null) {
            $openedStatusLabels = $this->issueStatusRepo->allOpenedLabels();
        }

        foreach ((new Tracker_SemanticManager($tracker))->getSemantics() as $semantic) {
            $semanticSaved = false;
            switch (get_class($semantic)) {
                case Tracker_Semantic_Title::class:
                    $semanticSaved = (new Tracker_Semantic_TitleDao())->save(
                        $tracker->id,
                        $trackerFieldIds[RedmineIssueColumnEnum::SUBJECT]
                    );
                    break;

                case Tracker_Semantic_Description::class:
                    $semanticSaved = (new Tracker_Semantic_DescriptionDao())->save(
                        $tracker->id,
                        $trackerFieldIds[RedmineIssueColumnEnum::DESCRIPTION]
                    );
                    break;

                case Tracker_Semantic_Status::class:
                    $statusField = $trackerFields[RedmineIssueColumnEnum::STATUS_ID];
                    if (!($statusField instanceof Tracker_FormElement_Field_List)) {
                        break;
                    }

                    $openedStatusIds = [];
                    foreach ($statusField->getAllValues() as $status) {
                        if (in_array($status->getLabel(), $openedStatusLabels)) {
                            $openedStatusIds[] = $status->getId();
                        }
                    }

                    $semanticSaved = (new Tracker_Semantic_StatusDao())->save(
                        $tracker->id,
                        $statusField->getId(),
                        $openedStatusIds
                    );
                    break;

                case Tracker_Semantic_Contributor::class:
                    $semanticSaved = (new \Tracker_Semantic_ContributorDao())->save(
                        $tracker->id,
                        $trackerFieldIds[RedmineIssueColumnEnum::ASSIGNED_TO_ID]
                    );
                    break;

                case SemanticTimeframe::class:
                    $semanticSaved = (new SemanticTimeframeDao)->save(
                        $tracker->id,
                        $trackerFieldIds[RedmineIssueColumnEnum::START_DATE],
                        null,
                        $trackerFieldIds[RedmineIssueColumnEnum::DUE_DATE]
                    );
                    break;

                case Tracker_Tooltip::class:
                    $tooltipManager = new Tracker_TooltipDao();
                    $semanticSaved = $tooltipManager->add($tracker->id, $trackerFieldIds[RedmineIssueColumnEnum::ID], 'end');
                    $semanticSaved = $semanticSaved && $tooltipManager->add($tracker->id, $trackerFieldIds[RedmineIssueColumnEnum::SUBJECT], 'end');
                    $semanticSaved = $semanticSaved && $tooltipManager->add($tracker->id, $trackerFieldIds[RedmineIssueColumnEnum::STATUS_ID], 'end');
                    break;
            }

            if (!$semanticSaved) {
                throw new Exception(sprintf('Failed to configure semantic for %s', $semantic->getShortName()));
            }
        }
    }
}
