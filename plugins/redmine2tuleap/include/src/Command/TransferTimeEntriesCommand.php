<?php

namespace Maximaster\Redmine2TuleapPlugin\Command;

use Exception;
use Maximaster\Redmine2TuleapPlugin\Enum\EntityTypeEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineIssueColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineTableEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineTimeEntryColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineUserColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Framework\GenericTransferCommand;
use Maximaster\Redmine2TuleapPlugin\Repository\PluginRedmine2TuleapReferenceRepository;
use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\EasyStatement;
use ParagonIE\EasyDB\Exception\MustBeNonEmpty;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tracker_ArtifactDao;
use TrackerFactory;
use Tuleap\Timetracking\Time\TimeDao;

class TransferTimeEntriesCommand extends GenericTransferCommand
{
    public const MINUTES_PER_HOUR = 60;

    /** @var TimeDao */
    private $timeDao;

    /** @var Tracker_ArtifactDao */
    private $artifactDao;

    /** @var TrackerFactory */
    private $trackerFactory;

    public static function getDefaultName()
    {
        return 'redmine2tuleap:time_entries:transfer';
    }

    protected function entityType(): EntityTypeEnum
    {
        return EntityTypeEnum::TIME_ENTRY();
    }

    public function __construct(
        string $pluginDirectory,
        EasyDB $redmineDb,
        EasyDB $tuleapDb,
        PluginRedmine2TuleapReferenceRepository $refRepo,
        TimeDao $timeDao,
        Tracker_ArtifactDao $artifactDao,
        TrackerFactory $trackerFactory
    ) {
        parent::__construct($pluginDirectory, $redmineDb, $tuleapDb, $refRepo);
        $this->timeDao = $timeDao;
        $this->artifactDao = $artifactDao;
        $this->trackerFactory = $trackerFactory;

        $this->artifactDao->enableExceptionsOnError();
    }

    protected function transfer(InputInterface $input, SymfonyStyle $output): int
    {
        $this->transferIssueLinkedTimeEntries($output);
        $this->transferProjectLinkedTimeEntries($output);
        return 0;
    }

    private function buildBaseTimeEntryQuery(bool $forIssues): string
    {
        $selected = preg_replace('/^/', RedmineTableEnum::TIME_ENTRIES . '.', [
            RedmineTimeEntryColumnEnum::ID,
            RedmineTimeEntryColumnEnum::USER_ID,
            RedmineTimeEntryColumnEnum::SPENT_ON,
            RedmineTimeEntryColumnEnum::HOURS,
            RedmineTimeEntryColumnEnum::COMMENTS,
        ]);

        $joins = '';
        if ($forIssues) {
            // it's possible that time_entries.project_id whould be wrong, so we fetching it from issues
            $selected[] = RedmineTableEnum::ISSUES . '.' . RedmineIssueColumnEnum::PROJECT_ID;

            $joins = '
                LEFT JOIN ' . RedmineTableEnum::ISSUES . ' ON
                    ' . RedmineTableEnum::ISSUES . '.' . RedmineIssueColumnEnum::ID . ' = ' . RedmineTableEnum::TIME_ENTRIES . '.' . RedmineTimeEntryColumnEnum::ISSUE_ID . '
            ';
        } else {
            $selected[] = RedmineTimeEntryColumnEnum::PROJECT_ID;
        }

        return '
            SELECT . ' . implode(', ', $selected) . '
            FROM ' . RedmineTableEnum::TIME_ENTRIES . '
            ' . $joins . '
        ';
    }

    /**
     * @param EasyStatement $conditions
     *
     * @throws MustBeNonEmpty
     */
    private function exludeAlreadyTransfered(EasyStatement $conditions): void
    {
        $transferedTimeEntryIds = $this->transferedRedmineIdList();
        if ($transferedTimeEntryIds) {
            $conditions->andIn(
                RedmineTableEnum::TIME_ENTRIES . '.' . RedmineTimeEntryColumnEnum::ID . ' not in (?*)',
                $transferedTimeEntryIds
            );
        }
    }

    /**
     * We have some reports of non-users in our instance for some reason
     * It's probably was wrong import from previous tracker
     *
     * @param string $query
     * @param EasyStatement $conditions
     */
    private function excludePseudoUsers(string &$query, EasyStatement $conditions)
    {
        $query .= '
            LEFT JOIN ' . RedmineTableEnum::USERS . ' ON
                ' . RedmineTableEnum::USERS . '.`' . RedmineUserColumnEnum::ID . '` = ' . RedmineTableEnum::TIME_ENTRIES . '.' . RedmineTimeEntryColumnEnum::USER_ID . ' AND
                ' . RedmineTableEnum::USERS . '.`' . RedmineUserColumnEnum::TYPE . '` = "User"
        ';

        $conditions->andWith(RedmineTableEnum::USERS . '.' . RedmineUserColumnEnum::ID . ' is not null');
    }

    private function transferIssueLinkedTimeEntries(SymfonyStyle $output)
    {
        $output->section('Transfering issue linked time entries');

        // transfer only those time entries which has their issues imported
        $transferedIssueIds = $this->refRepo->redmineIdsOfType(EntityTypeEnum::ISSUE());
        if (!$transferedIssueIds) {
            return;
        }

        $conditions = EasyStatement::open()->in(
            RedmineTableEnum::TIME_ENTRIES . '.' . RedmineTimeEntryColumnEnum::ISSUE_ID . ' in (?*)',
            $transferedIssueIds
        );

        $this->exludeAlreadyTransfered($conditions);

        $query = $this->buildBaseTimeEntryQuery(true);
        $this->excludePseudoUsers($query, $conditions);

        $timeEntries = $this->redmine()->run(
            $query .
            ' WHERE ' . $conditions->sql(),
            ...$conditions->values()
        );

        $this->transferTimeEntries($output, $timeEntries);
    }

    private function transferProjectLinkedTimeEntries(SymfonyStyle $output)
    {
        $output->section('Transfering project linked time entries');

        $conditions = EasyStatement::open()->with(RedmineTableEnum::TIME_ENTRIES . '.' . RedmineTimeEntryColumnEnum::ISSUE_ID . ' is null');

        $this->exludeAlreadyTransfered($conditions);

        $query = $this->buildBaseTimeEntryQuery(false);
        $this->excludePseudoUsers($query, $conditions);
        $this->includeOnlyTransferedProjects($conditions);

        $timeEntries = $this->redmine()->run(
            $query .
            ' WHERE ' . $conditions->sql(),
            ...$conditions->values()
        );

        $this->transferTimeEntries($output, $timeEntries);
    }

    private function transferTimeEntries(OutputInterface $output, array $timeEntries): void
    {
        $progress = $output->createProgressBar(count($timeEntries));
        foreach ($timeEntries as $timeEntry) {
            $this->transferTimeEntry($timeEntry);
            $progress->advance();
        }
    }

    private function transferTimeEntry(array $timeEntry): void
    {
        $artifactId = $this->getArtifactId(
            $timeEntry[RedmineTimeEntryColumnEnum::PROJECT_ID],
            $timeEntry[RedmineTimeEntryColumnEnum::ISSUE_ID] ?? null
        );

        $timeId = $this->timeDao->addTime(
            $this->refRepo->getTuleapUserId($timeEntry[RedmineTimeEntryColumnEnum::USER_ID]),
            $artifactId,
            $timeEntry[RedmineTimeEntryColumnEnum::SPENT_ON],
            $this->convertSpentTime($timeEntry[RedmineTimeEntryColumnEnum::HOURS]),
            $timeEntry[RedmineTimeEntryColumnEnum::COMMENTS] ?: ''
        );

        if (!$timeId) {
            throw new Exception(sprintf('Failed to transfer time entry #%d', $timeEntry[RedmineTimeEntryColumnEnum::ID]));
        }

        $this->markAsTransfered($timeEntry[RedmineTimeEntryColumnEnum::ID], $timeId);
    }

    private function getArtifactId(int $projectId, ?int $issueId): int
    {
        if (!$issueId) {
            return $this->getProjectArtifactId($projectId);
        }

        return $this->refRepo->getArtifactId($issueId);
    }

    /**
     * Convert spent hours to spent minutes
     *
     * @param float $spentHours
     *
     * @return int
     */
    private function convertSpentTime(float $spentHours): int
    {
        return (int) round($spentHours * self::MINUTES_PER_HOUR);
    }

    /**
     * Redmine allows to create time entries for a project (without issue), but Tuleap doesn't
     * So we had to create a bare artifact for those purpose, but just one per project
     * Also we should make sure that such an artifact hasn't been created in a previous run
     *
     * @param int $redmineProjectId
     */
    private function getProjectArtifactId(int $redmineProjectId): int
    {
        $tuleapProjectId = $this->refRepo->getTuleapProjectId($redmineProjectId);
        $tracker = $this->trackerFactory->getTrackerByShortnameAndProjectId(TransferProjectsCommand::TRACKER_ITEM_NAME, $tuleapProjectId);

        $artifactId = $this->generateProjectArtifactId($tuleapProjectId);

        if ($this->artifactDao->searchById($artifactId)->count()) {
            return $artifactId;
        }

        // you can't see those artifacts in site, because they don't have changeset
        // note: it doesn't break numbering despite big numbers generated in generateProjectArtifactId
        $this->artifactDao->createWithId(
            $artifactId,
            $tracker->id,
            0,
            time(),
            0
        );

        return $artifactId;
    }

    private function generateProjectArtifactId(int $projectId): int
    {
        // INT(11) can count up to 2147483648 (10 numbers)
        return 2 . str_pad((string) $projectId, 9, '0', STR_PAD_LEFT);
    }

    private function includeOnlyTransferedProjects(EasyStatement $conditions)
    {
        $redmineProjectIds = $this->refRepo->redmineIdsOfType(EntityTypeEnum::PROJECT());
        if (!$redmineProjectIds) {
            throw new Exception('Transfer some project first');
        }

        $conditions->andIn(RedmineTableEnum::TIME_ENTRIES . '.' . RedmineTimeEntryColumnEnum::PROJECT_ID . ' in (?*)', $redmineProjectIds);
    }
}
