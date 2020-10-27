<?php

namespace Maximaster\Redmine2TuleapPlugin\Enum;

use MyCLabs\Enum\Enum;

/**
 * @method static self USER()
 * @method static self PROJECT()
 * @method static self ISSUE()
 */
class EntityTypeEnum extends Enum
{
    public const USER = 'user';
    public const PROJECT = 'project';
    public const ISSUE = 'issue';
}
