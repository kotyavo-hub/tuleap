<?php

namespace Maximaster\Redmine2TuleapPlugin\Enum;

use MyCLabs\Enum\Enum;

class RedmineTimeEntryColumnEnum extends Enum
{
    public const ID = 'id';
    public const PROJECT_ID = 'project_id';
    public const USER_ID = 'user_id';
    public const ISSUE_ID = 'issue_id';
    public const HOURS = 'hours';
    public const COMMENTS = 'comments';
    public const ACTIVITY_ID = 'activity_id';
    public const SPENT_ON = 'spent_on';
    public const TYEAR = 'tyear';
    public const TMONTH = 'tmonth';
    public const TWEEK = 'tweek';
    public const CREATED_ON = 'created_on';
    public const UPDATED_ON = 'updated_on';
}
