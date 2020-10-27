<?php

namespace Maximaster\Redmine2TuleapPlugin\Repository;

use Maximaster\Redmine2TuleapPlugin\Enum\RedmineEnumerationColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineEnumerationTypeEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineTableEnum;
use ParagonIE\EasyDB\EasyDB;

class RedmineEnumerationRepository
{
    /** @var EasyDB */
    private $redmineDb;

    public function __construct(EasyDB $redmineDb)
    {
        $this->redmineDb = $redmineDb;
    }

    public function allOfType(RedmineEnumerationTypeEnum $type): array
    {
        return $this->redmineDb->run(
            '
                SELECT *
                FROM ' . RedmineTableEnum::ENUMERATIONS . '
                WHERE `' . RedmineEnumerationColumnEnum::TYPE . '` = ?
                ORDER BY ' . RedmineEnumerationColumnEnum::POSITION . ' ASC
            ',
            $type->getValue()
        );
    }

    public function allIssuePriorities(): array
    {
        return $this->allOfType(RedmineEnumerationTypeEnum::ISSUE_PRIORITY());
    }

    /**
     * @return string[]
     */
    public function allIssuePriorityNames(): array
    {
        return array_map(
            function (array $issuePriority) {
                return $issuePriority[RedmineEnumerationColumnEnum::NAME];
            },
            $this->allIssuePriorities()
        );
    }
}
