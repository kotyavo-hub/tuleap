<?php

namespace Maximaster\Redmine2TuleapPlugin\Enum;

use MyCLabs\Enum\Enum;

class RedmineUserStatusEnum extends Enum
{
    public const ACTIVE = 1;
    public const REGISTERED = 2;
    public const BLOCKED = 3;
}
