<?php

namespace Maximaster\Redmine2TuleapPlugin\Command;

use Exception;
use Maximaster\Redmine2TuleapPlugin\Enum\EntityTypeEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineCustomFieldColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineCustomFieldFormatEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineCustomValueColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineIssueColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineIssueStatusEnum;
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
use Maximaster\Redmine2TuleapPlugin\Enum\TuleapTrackerStringFieldColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Framework\GenericTransferCommand;
use Maximaster\Redmine2TuleapPlugin\Repository\PluginRedmine2TuleapReferenceRepository;
use Maximaster\Redmine2TuleapPlugin\Repository\RedmineCustomFieldRepository;
use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\EasyStatement;
use Project;
use ProjectManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tracker;
use Tracker_FormElement_Field_ArtifactLink;
use Tracker_FormElement_Field_List_Bind_Static;
use Tracker_FormElement_Field_List_Bind_Users;
use Tracker_FormElementFactory;
use TrackerFactory;
use Tuleap\Tracker\Creation\TrackerCreationSettings;
use Tuleap\Tracker\TrackerColor;

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

    public const ISSUE_STATUS_CONVERSION = [
        RedmineIssueStatusEnum::NEW => 'new',
        RedmineIssueStatusEnum::DISCUSSION => 'discussion',
        RedmineIssueStatusEnum::WORKING => 'working',
        RedmineIssueStatusEnum::REVIEW => 'review',
        RedmineIssueStatusEnum::TESTING => 'testing',
        RedmineIssueStatusEnum::DEPLOYING => 'deploying',
        RedmineIssueStatusEnum::SOLVED => 'solved',
        RedmineIssueStatusEnum::CLOSED => 'closed',
        RedmineIssueStatusEnum::REJECTED => 'rejected',
    ];

    public const ISSUE_CUSTOM_FIELD_FIELD_FORMAT_CONVERSION = [
        RedmineCustomFieldFormatEnum::DATE => Tracker_FormElementFactory::FIELD_DATE_TYPE,
        RedmineCustomFieldFormatEnum::TEXT => Tracker_FormElementFactory::FIELD_TEXT_TYPE,
        RedmineCustomFieldFormatEnum::BOOL => 'int',
        RedmineCustomFieldFormatEnum::USER => Tracker_FormElement_Field_List_Bind_Users::TYPE,
        RedmineCustomFieldFormatEnum::FLOAT => Tracker_FormElementFactory::FIELD_FLOAT_TYPE,
        RedmineCustomFieldFormatEnum::LIST => Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE,
        RedmineCustomFieldFormatEnum::ENUMERATION => null,
        RedmineCustomFieldFormatEnum::LINK => Tracker_FormElementFactory::FIELD_STRING_TYPE,
        RedmineCustomFieldFormatEnum::STRING => Tracker_FormElementFactory::FIELD_STRING_TYPE,
        RedmineCustomFieldFormatEnum::INT => 'int',
    ];

    /** @var RedmineCustomFieldRepository */
    private $cfRepo;

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

    public function __construct(
        EasyDB $redmineDb,
        EasyDB $tuleapDb,
        PluginRedmine2TuleapReferenceRepository $refRepo,
        RedmineCustomFieldRepository $cfRepo,
        ProjectManager $projectManager,
        TrackerFactory $trackerFactory,
        Tracker_FormElementFactory $trackerFormElementFactory
    )
    {
        parent::__construct($redmineDb, $tuleapDb, $refRepo);
        $this->cfRepo = $cfRepo;
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

            $created = $tuleapDb->insert(TuleapTableEnum::PROJECT_EXTRA_FIELD, [
                TuleapProjectExtraFieldColumnEnum::DESC_REQUIRED => $redmineProjectCustomField[RedmineCustomFieldColumnEnum::IS_REQUIRED],
                TuleapProjectExtraFieldColumnEnum::DESC_NAME => $redmineProjectCustomField[RedmineCustomFieldColumnEnum::NAME],
                TuleapProjectExtraFieldColumnEnum::DESC_DESCRIPTION => $redmineProjectCustomField[RedmineCustomFieldColumnEnum::DESCRIPTION],
                TuleapProjectExtraFieldColumnEnum::DESC_RANK => $redmineProjectCustomField[RedmineCustomFieldColumnEnum::POSITION],
                TuleapProjectExtraFieldColumnEnum::DESC_TYPE => $this->convertExtraFieldType(new RedmineCustomFieldFormatEnum($redmineProjectCustomField[RedmineCustomFieldColumnEnum::FIELD_FORMAT])),
            ]);

            if (!$created) {
                $output->error(
                    sprintf(
                        'Failed to create extra project field [%d] "%s": %d %d %s',
                        $redmineExtraFieldId,
                        $redmineProjectCustomField[RedmineCustomFieldColumnEnum::NAME],
                        ...$tuleapDb->errorInfo()
                    ));
                return -1;
            }

            $redmineProjectExtraFieldToTuleap[$redmineExtraFieldId] = $tuleapDb->lastInsertId();
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
        } catch (Exception $e) {
            throw new Exception(sprintf('Failed to create tracker template: %s', $e->getMessage()));
        }

        $trackerTemplate = $this->trackerFactory->getTrackerById($trackerId);
        $this->addDefaultTrackerFields($trackerTemplate);
        return $trackerTemplate;
    }

    private function addDefaultTrackerFields(Tracker $tracker): void
    {
        $fields = [
            [
                TuleapTrackerFieldColumnEnum::FORMELEMENT_TYPE => Tracker_FormElementFactory::FIELD_ARTIFACT_ID_TYPE,
                TuleapTrackerFieldColumnEnum::LABEL => RedmineIssueColumnEnum::ID,
            ],
            [
                TuleapTrackerFieldColumnEnum::FORMELEMENT_TYPE => Tracker_FormElementFactory::FIELD_STRING_TYPE,
                TuleapTrackerFieldColumnEnum::LABEL => RedmineIssueColumnEnum::SUBJECT,
                TuleapTrackerFieldColumnEnum::REQUIRED => true,
                self::SPECIFIC_PROPERTIES => [
                    TuleapTrackerStringFieldColumnEnum::MAXCHARS => 255,
                ],
            ],
            [
                TuleapTrackerFieldColumnEnum::FORMELEMENT_TYPE => Tracker_FormElementFactory::FIELD_TEXT_TYPE,
                TuleapTrackerFieldColumnEnum::LABEL => RedmineIssueColumnEnum::DESCRIPTION,
            ],
            [
                TuleapTrackerFieldColumnEnum::FORMELEMENT_TYPE => Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE,
                TuleapTrackerFieldColumnEnum::LABEL => RedmineIssueColumnEnum::STATUS_ID,
                TuleapTrackerFieldColumnEnum::REQUIRED => true,
                'bind-type' => Tracker_FormElement_Field_List_Bind_Static::TYPE,
                'bind' => ['add' => implode(PHP_EOL, self::ISSUE_STATUS_CONVERSION)],
            ],
            [
                TuleapTrackerFieldColumnEnum::FORMELEMENT_TYPE => 'int',
                TuleapTrackerFieldColumnEnum::LABEL => 'priority',
            ],
            [
                TuleapTrackerFieldColumnEnum::FORMELEMENT_TYPE => Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE,
                TuleapTrackerFieldColumnEnum::LABEL => RedmineIssueColumnEnum::ASSIGNED_TO_ID,
                'bind-type' => Tracker_FormElement_Field_List_Bind_Users::TYPE,
                'bind' => ['value_function' => ['project_members']],
            ],
            [
                TuleapTrackerFieldColumnEnum::FORMELEMENT_TYPE => Tracker_FormElement_Field_ArtifactLink::TYPE,
                TuleapTrackerFieldColumnEnum::LABEL => RedmineIssueColumnEnum::PARENT_ID,
            ],
            [
                TuleapTrackerFieldColumnEnum::FORMELEMENT_TYPE => Tracker_FormElementFactory::FIELD_DATE_TYPE,
                TuleapTrackerFieldColumnEnum::LABEL => RedmineIssueColumnEnum::START_DATE,
            ],
            [
                TuleapTrackerFieldColumnEnum::FORMELEMENT_TYPE => Tracker_FormElementFactory::FIELD_DATE_TYPE,
                TuleapTrackerFieldColumnEnum::LABEL => RedmineIssueColumnEnum::DUE_DATE,
            ],
            [
                TuleapTrackerFieldColumnEnum::FORMELEMENT_TYPE => 'int',
                TuleapTrackerFieldColumnEnum::LABEL => RedmineIssueColumnEnum::ESTIMATED_HOURS,
            ],
        ];

        foreach ($this->cfRepo->allOfIssue(RedmineCustomFieldColumnEnum::ID) as $customField) {
            $field = [
                TuleapTrackerFieldColumnEnum::FORMELEMENT_TYPE => $this->convertCustomFieldType($customField[RedmineCustomFieldColumnEnum::FIELD_FORMAT]),
                TuleapTrackerFieldColumnEnum::LABEL => $customField[RedmineCustomFieldColumnEnum::NAME],
            ];

            switch ($field[TuleapTrackerFieldColumnEnum::FORMELEMENT_TYPE]) {
                case Tracker_FormElement_Field_List_Bind_Users::TYPE:
                    $field = [
                        TuleapTrackerFieldColumnEnum::FORMELEMENT_TYPE => Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE,
                        'bind-type' => Tracker_FormElement_Field_List_Bind_Users::TYPE,
                        'bind' => ['value_function' => ['project_members']],
                    ] + $field;
                    break;

                case Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE:
                    $field += [
                        'bind-type' => Tracker_FormElement_Field_List_Bind_Static::TYPE,
                        'bind' => ['add' => $this->cfRepo->valuesOfField($customField[RedmineCustomFieldColumnEnum::ID])],
                    ];
                    break;
            }

            if ($customField[RedmineCustomFieldColumnEnum::MULTIPLE]) {
                switch ($field[TuleapTrackerFieldColumnEnum::FORMELEMENT_TYPE]) {
                    case Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE:
                        $field[TuleapTrackerFieldColumnEnum::FORMELEMENT_TYPE] = Tracker_FormElementFactory::FIELD_MULTI_SELECT_BOX_TYPE;
                        break;
                }
            }

            $fields[] = $field;
        }

        foreach ($fields as $fieldRank => $field) {
            $this->trackerFormElementFactory->createFormElement(
                $tracker,
                $field[TuleapTrackerFieldColumnEnum::FORMELEMENT_TYPE],
                $field + [
                    TuleapTrackerFieldColumnEnum::RANK => $fieldRank + 1,
                    TuleapTrackerFieldColumnEnum::USE_IT => 1,
                ],
                true,
                true
            );
        }
    }

    private function convertCustomFieldType(string $redmineCustomFieldFormat): string
    {

    }
}
