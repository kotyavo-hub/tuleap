<?php

namespace Maximaster\Redmine2TuleapPlugin\Enum;

use MyCLabs\Enum\Enum;

class RedmineEnumerationColumnEnum extends Enum
{
    public const ID = 'id';
    public const NAME = 'name';
    public const POSITION = 'position';
    public const IS_DEFAULT = 'is_default';
    public const TYPE = 'type';
    public const ACTIVE = 'active';
    public const PROJECT_ID = 'project_id';
    public const PARENT_ID = 'parent_id';
    public const POSITION_NAME = 'position_name';
}
