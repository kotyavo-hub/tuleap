<?php

namespace Maximaster\Redmine2TuleapPlugin\Enum;

use MyCLabs\Enum\Enum;

class RedmineIssueStatusColumnEnum extends Enum
{
    public const ID = 'id';
    public const NAME = 'name';
    public const IS_CLOSED = 'is_closed';
    public const POSITION = 'position';
    public const DEFAULT_DONE_RATIO    = 'default_done_ratio';
}
