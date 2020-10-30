<?php

namespace Maximaster\Redmine2TuleapPlugin\Enum;

use MyCLabs\Enum\Enum;

class RedmineMemberColumnEnum extends Enum
{
    public const ID = 'id';
    public const USER_ID = 'user_id';
    public const PROJECT_ID = 'project_id';
    public const CREATED_ON = 'created_on';
    public const MAIL_NOTIFICATION = 'mail_notification';
}
