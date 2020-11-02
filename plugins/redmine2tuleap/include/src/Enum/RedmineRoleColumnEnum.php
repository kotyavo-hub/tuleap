<?php

namespace Maximaster\Redmine2TuleapPlugin\Enum;

use MyCLabs\Enum\Enum;

class RedmineRoleColumnEnum extends Enum
{
    public const ID = 'id';
    public const NAME = 'name';
    public const POSITION = 'position';
    public const ASSIGNABLE = 'assignable';
    public const BUILTIN = 'builtin';
    public const PERMISSIONS = 'permissions';
    public const ISSUES_VISIBILITY = 'issues_visibility';
    public const USERS_VISIBILITY = 'users_visibility';
    public const TIME_ENTRIES_VISIBILITY = 'time_entries_visibility';
    public const ALL_ROLES_MANAGED = 'all_roles_managed';
}
