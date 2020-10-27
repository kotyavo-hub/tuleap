<?php

namespace Maximaster\Redmine2TuleapPlugin\Command;

use Maximaster\Redmine2TuleapPlugin\Enum\EntityTypeEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineIssueColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineTableEnum;
use Maximaster\Redmine2TuleapPlugin\Framework\GenericTransferCommand;
use Maximaster\Redmine2TuleapPlugin\Repository\PluginRedmine2TuleapReferenceRepository;
use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\EasyStatement;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tracker_ArtifactFactory;
use TrackerFactory;

class TransferIssuesCommand extends GenericTransferCommand
{
    /** @var Tracker_ArtifactFactory */
    private $trackerArtifactFactory;

    /** @var TrackerFactory */
    private $trackerFactory;

    protected function entityType(): EntityTypeEnum
    {
        return EntityTypeEnum::ISSUE();
    }

    public function __construct(
        EasyDB $redmineDb,
        EasyDB $tuleapDb,
        PluginRedmine2TuleapReferenceRepository $refRepo,
        Tracker_ArtifactFactory $trackerArtifactFactory,
        TrackerFactory $trackerFactory
    ) {
        parent::__construct($redmineDb, $tuleapDb, $refRepo);
        $this->trackerArtifactFactory = $trackerArtifactFactory;
        $this->trackerFactory = $trackerFactory;
    }

    protected function transfer(InputInterface $input, SymfonyStyle $output): int
    {
        $redmineDb = $this->redmine();

        $issuesQueryValues = [];
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
                RedmineIssueColumnEnum::CLOSED_ON,
                // RedmineIssueColumnEnum::EXPIRATION_DATE,
                // RedmineIssueColumnEnum::FIRST_RESPONSE_DATE,
                // RedmineIssueColumnEnum::ISSUE_SLA,
                // RedmineIssueColumnEnum::SYNCHRONY_ID,
                // RedmineIssueColumnEnum::SYNCHRONIZED_AT,
            ]). '
            FROM ' . RedmineTableEnum::ISSUES . '
        ';

        if ($transferedIssueIds = $this->transferedRedmineIdList()) {
            $issuesQuery = ' WHERE ' . EasyStatement::open()->in(RedmineIssueColumnEnum::ID . ' not in (?*)', $transferedIssueIds);
            $issuesQueryValues = array_merge($issuesQueryValues, $transferedIssueIds);
        }

        $projectType = EntityTypeEnum::PROJECT();

        $redmineIssues = $redmineDb->run($issuesQuery, ...$issuesQueryValues);
        foreach ($redmineIssues as $redmineIssue) {
            $redmineProjectId = $redmineIssue[RedmineIssueColumnEnum::PROJECT_ID];
            $tuleapProjectId = $this->refRepo->findTuleapId($projectType, (string) $redmineProjectId);

            $tracker = $this->trackerFactory->getTrackerByShortnameAndProjectId(TransferProjectsCommand::TRACKER_ITEM_NAME, $tuleapProjectId);
            // $this->trackerArtifactFactory->createArtifact($tracker, $tuleapArtifact, ...);
        }
    }
}
