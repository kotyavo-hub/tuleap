<?php

namespace Maximaster\Redmine2TuleapPlugin\Enum;

use MyCLabs\Enum\Enum;

class RedmineIssueColumnEnum extends Enum
{
    public const ID = 'id';
    public const TRACKER_ID = 'tracker_id';
    public const PROJECT_ID = 'project_id';
    public const SUBJECT = 'subject';
    public const DESCRIPTION = 'description';
    public const DUE_DATE = 'due_date';
    public const CATEGORY_ID = 'category_id';
    public const STATUS_ID = 'status_id';
    public const ASSIGNED_TO_ID = 'assigned_to_id';
    public const PRIORITY_ID = 'priority_id';
    public const FIXED_VERSION_ID = 'fixed_version_id';
    public const AUTHOR_ID = 'author_id';
    public const LOCK_VERSION = 'lock_version';
    public const CREATED_ON = 'created_on';
    public const UPDATED_ON = 'updated_on';
    public const START_DATE = 'start_date';
    public const DONE_RATIO = 'done_ratio';
    public const ESTIMATED_HOURS = 'estimated_hours';
    public const PARENT_ID = 'parent_id';
    public const ROOT_ID = 'root_id';
    public const LFT = 'lft';
    public const RGT = 'rgt';
    public const IS_PRIVATE = 'is_private';
    public const CLOSED_ON = 'closed_on';
    public const EXPIRATION_DATE = 'expiration_date';
    public const FIRST_RESPONSE_DATE = 'first_response_date';
    public const ISSUE_SLA = 'issue_sla';
    public const SYNCHRONY_ID = 'synchrony_id';
    public const SYNCHRONIZED_AT = 'synchronized_at';
}
