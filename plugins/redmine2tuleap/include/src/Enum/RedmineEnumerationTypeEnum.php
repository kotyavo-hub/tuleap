<?php

namespace Maximaster\Redmine2TuleapPlugin\Enum;

use MyCLabs\Enum\Enum;

/**
 * @method static self ISSUE_PRIORITY()
 * @method static self TIME_ENTRY_ACTIVITY()
 */
class RedmineEnumerationTypeEnum extends Enum
{
    public const ISSUE_PRIORITY = 'IssuePriority';
    public const TIME_ENTRY_ACTIVITY = 'TimeEntryActivity';
}
