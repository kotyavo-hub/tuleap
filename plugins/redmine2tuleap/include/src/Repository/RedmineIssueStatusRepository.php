<?php

namespace Maximaster\Redmine2TuleapPlugin\Repository;

use Maximaster\Redmine2TuleapPlugin\Enum\RedmineIssueStatusColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineTableEnum;
use ParagonIE\EasyDB\EasyDB;

class RedmineIssueStatusRepository
{
    /** @var EasyDB */
    private $connection;

    /** @var array */
    private $cache;

    public function __construct(EasyDB $connection)
    {
        $this->connection = $connection;
    }

    public function all(): array
    {
        if ($this->cache === null) {
            $this->cache = $this->connection->run('SELECT * FROM ' . RedmineTableEnum::ISSUE_STATUSES);
        }
        return $this->cache;
    }

    public function allOpened(): array
    {
        return array_filter($this->all(), function (array $issueStatus) {
            return ! $issueStatus[RedmineIssueStatusColumnEnum::IS_CLOSED];
        });
    }

    /**
     * @return string[]
     */
    public function allOpenedLabels(): array
    {
        return array_map(
            function (array $issueStatus) {
                return $issueStatus[RedmineIssueStatusColumnEnum::NAME];
            },
            $this->allOpened()
        );
    }

    /**
     * @return string[]
     */
    public function allLabels(): array
    {
        return array_map(
            function (array $issueStatus) {
                return $issueStatus[RedmineIssueStatusColumnEnum::NAME];
            },
            $this->all()
        );
    }
}
