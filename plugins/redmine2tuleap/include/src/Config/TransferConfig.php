<?php

namespace Maximaster\Redmine2TuleapPlugin\Config;

use BaseLanguage;

class TransferConfig
{
    /**
     * Redmine project directory to import files from
     *
     * @var string
     */
    private $redmineDirectory;

    /**
     * Some object could refer deleted users, but we should link them to someone
     * @var int
     */
    private $defaultRedmineUserId;

    /**
     * Custom field ids which shouldn't be transfered into trackers
     *
     * @var int[]
     */
    private $excludedCustomFields;

    /**
     * Default locale for users
     *
     * @var string
     */
    private $language;

    /**
     * If your Tuleap instance is new, you can create artifacts with the same ids from Redmine issues
     *
     * var bool
     */
    private $createArtifactIdFromIssueId;

    /**
     * Should we ignore (true) or transfer private issues?
     * The problem is that in Tuleap we don't have "private" restriction on specifict artifacts, so after transfering
     * it would be available for everyone which could be
     *
     * @var bool
     */
    private $ignorePrivateIssues;

    /**
     * Should we ignore (true) or transfer (false) private notes?
     * The problem is that in Tuleap we don't have "private" restriction on specifict follow-ups, so after transfering
     * it would be available for everyone which could cause problems
     *
     * @var bool
     */
    private $ignorePrivateNotes;

    public function __construct(
        string $redmineDirectory,
        int $defaultRedmineUserId,
        string $language = BaseLanguage::DEFAULT_LANG,
        array $excludedCustomFields = [],
        bool $createArtifactIdFromIssueId = false,
        bool $ignorePrivateIssues = true,
        bool $ignorePrivateNotes = true
    ) {
        $this->redmineDirectory = $redmineDirectory;
        $this->defaultRedmineUserId = $defaultRedmineUserId;
        $this->language = $language;
        $this->excludedCustomFields = $excludedCustomFields;
        $this->createArtifactIdFromIssueId = $createArtifactIdFromIssueId;
        $this->ignorePrivateIssues = $ignorePrivateIssues;
        $this->ignorePrivateNotes = $ignorePrivateNotes;
    }

    public function redmineDirectory(): string
    {
        return $this->redmineDirectory;
    }

    public function defaultRedmineUserId(): int
    {
        return $this->defaultRedmineUserId;
    }

    /**
     * @return int[]
     */
    public function excludedCustomFields(): array
    {
        return $this->excludedCustomFields;
    }

    public function language(): string
    {
        return $this->language;
    }

    public function createArtifactIdFromIssueId(): bool
    {
        return $this->createArtifactIdFromIssueId;
    }

    public function ignorePrivateIssues(): bool
    {
        return $this->ignorePrivateIssues;
    }

    public function ignorePrivateNotes(): bool
    {
        return $this->ignorePrivateNotes;
    }
}
