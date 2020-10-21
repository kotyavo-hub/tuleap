<?php

namespace Maximaster\Redmine2TuleapPlugin\Enum;

use MyCLabs\Enum\Enum;

class RedmineProjectStatusEnum extends Enum
{
    public const OPENED = 1;
    public const CLOSED = 5;
    public const ARCHIVED = 9;
}
