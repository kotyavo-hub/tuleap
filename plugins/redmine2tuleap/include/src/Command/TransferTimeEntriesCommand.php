<?php

namespace Maximaster\Redmine2TuleapPlugin\Command;

use Maximaster\Redmine2TuleapPlugin\Enum\EntityTypeEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineIssueColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineTableEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineTimeEntryColumnEnum;
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
    }

    protected function transfer(InputInterface $input, SymfonyStyle $output): int
    {
        $this->transferIssueLinkedTimeEntries($output);
        $this->transferProjectLinkedTimeEntries($output);
        return 0;
    }

    private function buildBaseTimeEntryQuery(): string
    {
        return '
            SELECT ' . implode(', ', array_merge(
                preg_replace('/^/', RedmineTableEnum::TIME_ENTRIES . '.', [
                    RedmineTimeEntryColumnEnum::ID,
                    RedmineTimeEntryColumnEnum::USER_ID,
                    RedmineTimeEntryColumnEnum::SPENT_ON,
                    RedmineTimeEntryColumnEnum::HOURS,
                    RedmineTimeEntryColumnEnum::COMMENTS,
                ]),
                [
                    // it's possible that time_entries.project_id whould be wrong, so we fetching it from issues
                    RedmineTableEnum::ISSUES . '.' . RedmineIssueColumnEnum::PROJECT_ID
                ]
            )) . '
            FROM ' . RedmineTableEnum::TIME_ENTRIES . '
            LEFT JOIN ' . RedmineTableEnum::ISSUES . ' ON
                ' . RedmineTableEnum::ISSUES . '.' . RedmineIssueColumnEnum::ID . ' = ' . RedmineTableEnum::TIME_ENTRIES . '.' . RedmineTimeEntryColumnEnum::ISSUE_ID . '
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

    private function transferIssueLinkedTimeEntries(SymfonyStyle $output)
    {
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

        $timeEntries = $this->redmine()->run(
            $this->buildBaseTimeEntryQuery() .
            ' WHERE ' . $conditions->sql(),
            ...$conditions->values()
        );

        $this->transferTimeEntries($output, $timeEntries);
    }

    private function transferProjectLinkedTimeEntries(SymfonyStyle $output)
    {
        $conditions = EasyStatement::open()->with(RedmineTableEnum::TIME_ENTRIES . '.' . RedmineTimeEntryColumnEnum::ISSUE_ID . ' is null');

        $this->exludeAlreadyTransfered($conditions);

        $timeEntries = $this->redmine()->run(
            $this->buildBaseTimeEntryQuery() .
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
            $timeEntry[RedmineTimeEntryColumnEnum::ISSUE_ID] ?: null
        );

        $this->timeDao->addTime(
            $this->refRepo->getTuleapUserId($timeEntry[RedmineTimeEntryColumnEnum::USER_ID]),
            $artifactId,
            $timeEntry[RedmineTimeEntryColumnEnum::SPENT_ON],
            $this->convertSpentTime($timeEntry[RedmineTimeEntryColumnEnum::HOURS]),
            $timeEntry[RedmineTimeEntryColumnEnum::COMMENTS]
        );
    }

    private function getArtifactId(int $projectId, ?int $issueId): int
    {
        if ($issueId === null) {
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

        $artifact = $this->artifactDao->searchById($artifactId);

        if ($artifact === false) {
            $this->artifactDao->createWithId(
                $artifactId,
                $tracker->id,
                0,
                time(),
                0
             );
        }

        return $artifactId;
    }

    private function generateProjectArtifactId(int $projectId): int
    {
        return 1 . str_pad((string) $projectId, 10, '0', STR_PAD_LEFT);
    }
}
