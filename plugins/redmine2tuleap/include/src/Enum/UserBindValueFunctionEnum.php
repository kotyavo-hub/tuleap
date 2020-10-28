<?php

namespace Maximaster\Redmine2TuleapPlugin\Enum;

use MyCLabs\Enum\Enum;

class UserBindValueFunctionEnum extends Enum
{
    public const GROUP_MEMBERS = 'group_members';
    public const PROJECT_MEMBERS = self::GROUP_MEMBERS;
}
