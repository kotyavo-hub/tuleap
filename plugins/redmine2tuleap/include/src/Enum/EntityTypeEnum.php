<?php

namespace Maximaster\Redmine2TuleapPlugin\Enum;

use MyCLabs\Enum\Enum;

/**
 * @method static self USER()
 * @method static self PROJECT()
 * @method static self ISSUE()
 * @method static self TIME_ENTRY()
 * @method static self WIKI_PAGE()
 * @method static self ATTACHMENT()
 */
class EntityTypeEnum extends Enum
{
    public const USER = 'user';
    public const PROJECT = 'project';
    public const ISSUE = 'issue';
    public const TIME_ENTRY = 'time_entry';
    public const WIKI_PAGE = 'wiki_page';
    public const ATTACHMENT = 'attachment';
}
