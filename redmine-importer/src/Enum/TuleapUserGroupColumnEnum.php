<?php

namespace Maximaster\RedmineTuleapImporter\Enum;

use MyCLabs\Enum\Enum;

class TuleapUserGroupColumnEnum extends Enum
{
    public const USER_GROUP_ID = 'user_group_id';
    public const USER_ID = 'user_id';
    public const GROUP_ID = 'group_id';
    public const ADMIN_FLAGS = 'admin_flags';
    public const BUG_FLAGS = 'bug_flags';
    public const FORUM_FLAGS = 'forum_flags';
    public const PROJECT_FLAGS = 'project_flags';
    public const PATCH_FLAGS = 'patch_flags';
    public const SUPPORT_FLAGS = 'support_flags';
    public const FILE_FLAGS = 'file_flags';
    public const WIKI_FLAGS = 'wiki_flags';
    public const SVN_FLAGS = 'svn_flags';
    public const NEWS_FLAGS = 'news_flags';
}
