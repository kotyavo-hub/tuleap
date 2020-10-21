<?php

namespace Maximaster\Redmine2TuleapPlugin\Command;

use Exception;
use Maximaster\Redmine2TuleapPlugin\Enum\EntityTypeEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineCustomFieldColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineCustomFieldFormatEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineCustomValueColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineProjectColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineProjectStatusEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineTableEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\TuleapProjectColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\TuleapProjectExtraFieldColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\TuleapProjectExtraFieldTypeEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\TuleapProjectExtraValueColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\TuleapProjectTypeEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\TuleapTableEnum;
use Maximaster\Redmine2TuleapPlugin\Framework\GenericTransferCommand;
use Maximaster\Redmine2TuleapPlugin\Repository\RedmineCustomFieldRepository;
use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\EasyStatement;
use Project;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TransferProjectsCommand extends GenericTransferCommand
{
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

    /** @var RedmineCustomFieldRepository */
    private $cfRepo;

    public static function getDefaultName()
    {
        return 'redmine2tuleap:projects:transfer';
    }

    public function __construct(EasyDB $redmineDb, EasyDB $tuleapDb, RedmineCustomFieldRepository $cfRepo)
    {
        parent::__construct($redmineDb, $tuleapDb);
        $this->cfRepo = $cfRepo;
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

        $projectSelect = [
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
        ];

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

        $projectSelectQuery .= ' ORDER BY ' . RedmineProjectColumnEnum::ID . ' ASC';

        $projectSelectQuery = 'SELECT ' . implode(', ', $projectSelect) . ' ' . $projectSelectQuery;

        $redmineProjects = $redmineDb->run($projectSelectQuery, ...$queryValues);

        if (!$redmineProjects) {
            $output->note('Nothing to import. Exit');
            return 0;
        }

        $redmineProjectCount = count($redmineProjects);
        $output->note(sprintf('Going to import %d project%s', $redmineProjectCount, $redmineProjectCount > 1));

        $projectType = EntityTypeEnum::PROJECT();

        foreach ($redmineProjects as $redmineProject) {
            $projectAdded = $tuleapDb->insert(TuleapTableEnum::PROJECTS, [
                TuleapProjectColumnEnum::GROUP_NAME => $redmineProject[RedmineProjectColumnEnum::NAME],
                TuleapProjectColumnEnum::ACCESS => $redmineProject[RedmineProjectColumnEnum::IS_PUBLIC] ? Project::ACCESS_PUBLIC : Project::ACCESS_PRIVATE,
                TuleapProjectColumnEnum::STATUS => $this->converStatus($redmineProject[RedmineProjectColumnEnum::STATUS]),
                TuleapProjectColumnEnum::HTTP_DOMAIN => sprintf('%s._DOMAIN_NAME_', $redmineProject[RedmineProjectColumnEnum::IDENTIFIER]),
                TuleapProjectColumnEnum::SHORT_DESCRIPTION => $redmineProject[RedmineProjectColumnEnum::DESCRIPTION],
                TuleapProjectColumnEnum::TYPE => TuleapProjectTypeEnum::PROJECT,
            ]);

            if (!$projectAdded) {
                $output->error(sprintf('Failed to create project "%s": %d %d %s', $redmineProject[RedmineProjectColumnEnum::ID], ...$tuleapDb->errorInfo()));
                return -1;
            }

            $tuleapProjectId = $tuleapDb->lastInsertId();
            try {
                $this->markAsTransfered($projectType, (string) $redmineProject[RedmineProjectColumnEnum::ID], (string) $tuleapProjectId);
            } catch (Exception $e) {
                $output->error(sprintf('Failed to mark %s "%s" as transfered', $projectType->getValue(), $e->getMessage()));
                return -1;
            }

            foreach ($redmineProjectExtraFieldToTuleap as $redmineCustomFieldId => $tuleapProjectExtraFieldId) {
                $fieldKey = $this->getProjectExtraFieldValueAlias($tuleapProjectExtraFieldId);

                if (empty($redmineProject[$fieldKey])) {
                    continue;
                }

                $tuleapDb->insert(TuleapTableEnum::PROJECT_EXTRA_VALUE, [
                    TuleapProjectExtraValueColumnEnum::GROUP_ID => $tuleapProjectId,
                    TuleapProjectExtraValueColumnEnum::DESC_VALUE_ID => $tuleapProjectExtraFieldId,
                    TuleapProjectExtraValueColumnEnum::VALUE => $redmineProject[$fieldKey],
                ]);
            }
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
}
