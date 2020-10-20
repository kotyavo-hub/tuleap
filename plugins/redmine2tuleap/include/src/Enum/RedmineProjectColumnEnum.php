<?php

namespace Maximaster\Redmine2TuleapPlugin\Enum;

use MyCLabs\Enum\Enum;

class RedmineProjectColumnEnum extends Enum
{
    public const ID = 'id';
    public const NAME = 'name';
    public const DESCRIPTION = 'description';
    public const HOMEPAGE = 'homepage';
    public const IS_PUBLIC = 'is_public';
    public const PARENT_ID = 'parent_id';
    public const CREATED_ON = 'created_on';
    public const UPDATED_ON = 'updated_on';
    public const IDENTIFIER = 'identifier';
    public const STATUS = 'status';
    public const LFT = 'lft';
    public const RGT = 'rgt';
    public const INHERIT_MEMBERS = 'inherit_members';
    public const DEFAULT_ASSIGNEE_ID = 'default_assignee_id';
    public const DEFAULT_VERSION_ID = 'default_version_id';
}
