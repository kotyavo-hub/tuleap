<?php

namespace Maximaster\RedmineTuleapImporter\Enum;

use MyCLabs\Enum\Enum;

/**
 * @method static self ISSUE()
 * @method static self PROJECT()
 * @method static self TIME_ENTRY()
 * @method static self USER()
 */
class RedmineCustomFieldTypeEnum extends Enum
{
    public const ISSUE = 'IssueCustomField';
    public const PROJECT = 'ProjectCustomField';
    public const TIME_ENTRY = 'TimeEntryCustomField';
    public const USER = 'UserCustomField';
}
