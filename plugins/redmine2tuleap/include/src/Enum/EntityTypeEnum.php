<?php

namespace Maximaster\Redmine2TuleapPlugin\Enum;

use MyCLabs\Enum\Enum;

/**
 * @method static self USER()
 * @method static self PROJECT()
 * @method static self ISSUE()
 * @method static self ISSUE_NOTE()
 * @method static self TIME_ENTRY()
 */
class EntityTypeEnum extends Enum
{
    public const USER = 'user';
    public const PROJECT = 'project';
    public const ISSUE = 'issue';
    public const ISSUE_NOTE = 'issue-note';
    public const TIME_ENTRY = 'time-entry';
}
