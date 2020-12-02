<?php

namespace Maximaster\Redmine2TuleapPlugin\Command;

use Exception;
use Maximaster\Redmine2TuleapPlugin\Enum\EntityTypeEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineJournalEntryColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineTableEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\TuleapTableEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\TuleapTrackerArtifactColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Framework\GenericTransferCommand;
use Maximaster\Redmine2TuleapPlugin\Repository\PluginRedmine2TuleapReferenceRepository;
use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\EasyStatement;
use PFUser;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tracker_Artifact;
use Tracker_Artifact_Changeset_Comment;
use Tracker_Artifact_Changeset_CommentDao;
use Tracker_Artifact_ChangesetDao;
use Netcarver\Textile;

class TransferIssueNotesCommand extends GenericTransferCommand
{
    /** @var Tracker_Artifact[] */
    private $artifactCache = [];

    /** @var Tracker_Artifact_ChangesetDao */
    private $trackerArtifactChangesetDao;

    /** @var Tracker_Artifact_Changeset_CommentDao */
    private $trackerArtifactChangesetCommentDao;

    /** @var Textile\Parser */
    private $textileParser;

    public static function getDefaultName()
    {
        return 'redmine2tuleap:issue-notes:transfer';
    }

    public function getDescription()
    {
        return 'Transfers Redmine journals as Tuleap issue follow-ups';
    }

    protected function entityType(): EntityTypeEnum
    {
        return EntityTypeEnum::ISSUE_NOTE();
    }

    public function __construct(
        string $pluginDirectory,
        EasyDB $redmineDb,
        EasyDB $tuleapDb,
        PluginRedmine2TuleapReferenceRepository $refRepo,
        Tracker_Artifact_ChangesetDao $trackerArtifactChangesetDao,
        Tracker_Artifact_Changeset_CommentDao $trackerArtifactChangesetCommentDao,
        Textile\Parser $textileParser
    ) {
        parent::__construct($pluginDirectory, $redmineDb, $tuleapDb, $refRepo);

        $this->trackerArtifactChangesetDao = $trackerArtifactChangesetDao;
        $this->trackerArtifactChangesetCommentDao = $trackerArtifactChangesetCommentDao;
        $this->textileParser = $textileParser;
    }

    protected function transfer(InputInterface $input, SymfonyStyle $output): int
    {
        $config = $this->config();
        $redmineDb = $this->redmine();

        $issueNotesQuery = '
            SELECT ' . implode(', ', [
                RedmineJournalEntryColumnEnum::ID,
                RedmineJournalEntryColumnEnum::JOURNALIZED_ID,
                RedmineJournalEntryColumnEnum::USER_ID,
                RedmineJournalEntryColumnEnum::NOTES,
                RedmineJournalEntryColumnEnum::CREATED_ON,
                RedmineJournalEntryColumnEnum::PRIVATE_NOTES,
            ]) . '
            FROM ' . RedmineTableEnum::JOURNALS;

        $issueNotesCondition = EasyStatement::open()
            ->with(RedmineJournalEntryColumnEnum::JOURNALIZED_ID . ' > 0')
            ->andWith(RedmineJournalEntryColumnEnum::NOTES . ' != ""')
            ->andWith(RedmineJournalEntryColumnEnum::JOURNALIZED_TYPE . ' = "Issue"');

        $importedIssueId = $this->refRepo->redmineIdsOfType(EntityTypeEnum::ISSUE());
        if (!$importedIssueId) {
            $output->error('Import some issues first');
            return -1;
        }

        $issueNotesCondition->andIn(RedmineJournalEntryColumnEnum::JOURNALIZED_ID . ' in (?*)', $importedIssueId);

        if ($transferedJournalEntryIds = $this->transferedRedmineIdList()) {
            $issueNotesCondition->andIn(
                RedmineJournalEntryColumnEnum::JOURNALIZED_ID . ' not in (?*)',
                $transferedJournalEntryIds
            );
        }

        if ($config->ignorePrivateNotes()) {
            $issueNotesCondition->andWith(RedmineJournalEntryColumnEnum::PRIVATE_NOTES . ' = 0');
        }

        $issueNotes = $redmineDb->run(
            $issueNotesQuery
                . ' WHERE ' . $issueNotesCondition->sql()
                . ' ORDER BY ' . RedmineJournalEntryColumnEnum::ID . ' ASC',
            ...$issueNotesCondition->values()
        );

        $issueNotesCnt = count($issueNotes);

        $output->section(sprintf('Transfering %d issue note%s', $issueNotesCnt, $issueNotesCnt ? 's' : ''));

        $progress = $output->createProgressBar($issueNotesCnt);

        foreach ($issueNotes as $issueNote) {
            try {
                $followUpChangesetId = $this->transferIssueNote($issueNote);
                $this->markAsTransfered($issueNote[RedmineJournalEntryColumnEnum::ID], $followUpChangesetId);
            } catch (Exception $e) {
                $output->error(
                    sprintf(
                        'Failed to create follow-up for issue #%d: %s',
                        $issueNote[RedmineJournalEntryColumnEnum::JOURNALIZED_ID],
                        $e->getMessage()
                    )
                );
                return -1;
            }

            $progress->advance();
        }

        return 0;
    }

    private function transferIssueNote(array $issueNote): int
    {
        $artifactId = $this->refRepo->getArtifactId($issueNote[RedmineJournalEntryColumnEnum::JOURNALIZED_ID]);
        $submittedById = $this->refRepo->getTuleapUserId($issueNote[RedmineJournalEntryColumnEnum::USER_ID], true);

        $htmlNotes = $this->textileParser->parse($issueNote[RedmineJournalEntryColumnEnum::NOTES])
            ?: $issueNote[RedmineJournalEntryColumnEnum::NOTES];

        $artifact = $this->buildArtifact($artifactId);

        $changeset = $artifact->createNewChangesetWithoutRequiredValidation(
            [],
            $htmlNotes,
            new PFUser(['user_id' => $submittedById]),
            false,
            Tracker_Artifact_Changeset_Comment::HTML_COMMENT
        );

        return $changeset->getId();
    }

//    // won't work because we have to create changeset values for each follow up, even there are no changes at all
//    private function rawCreateFollowUp()
//    {
//        $noteId = $issueNote[RedmineJournalEntryColumnEnum::ID];
//        $changesetId = $this->trackerArtifactChangesetDao->create(
//            $artifactId,
//            $submittedById,
//            null,
//            $issueNote[RedmineJournalEntryColumnEnum::CREATED_ON]
//        );
//
//        if (!$changesetId) {
//            throw new Exception(sprintf('Failed to create changeset for note #%d', $noteId));
//        }
//
//        $htmlNotes = $this->textileParser->parse($issueNote[RedmineJournalEntryColumnEnum::NOTES])
//            ?: $issueNote[RedmineJournalEntryColumnEnum::NOTES];
//
//        $followUpId = $this->trackerArtifactChangesetCommentDao->createNewVersion(
//            $changesetId,
//            $htmlNotes,
//            $submittedById,
//            $issueNote[RedmineJournalEntryColumnEnum::CREATED_ON],
//            0,
//            Tracker_Artifact_Changeset_Comment::HTML_COMMENT
//        );
//
//        if (!$followUpId) {
//            throw new Exception(sprintf('Failed to create comment for note #%d of changeset #%d', $noteId, $changesetId));
//        }
//    }

    private function buildArtifact(string $artifactId): Tracker_Artifact
    {
        if (!empty($this->artifactCache[$artifactId])) {
            return $this->artifactCache[$artifactId];
        }

        $trackerId = $this->tuleap()->cell(
            '
                SELECT ' . TuleapTrackerArtifactColumnEnum::TRACKER_ID . '
                FROM ' . TuleapTableEnum::TRACKER_ARTIFACT . '
                WHERE ' . TuleapTrackerArtifactColumnEnum::ID . ' = ?
            ',
            $artifactId
        );

        return $this->artifactCache[$artifactId] = new Tracker_Artifact($artifactId, $trackerId, 0, 0, 0);
    }
}
