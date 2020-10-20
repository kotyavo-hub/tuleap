<?php

namespace Maximaster\Redmine2TuleapPlugin\Enum;

use MyCLabs\Enum\Enum;

class TuleapUserAccessColumnEnum extends Enum
{
    public const USER_ID = 'user_id';
    public const LAST_ACCESS_DATE = 'last_access_date';
    public const PREV_AUTH_SUCCESS = 'prev_auth_success';
    public const LAST_AUTH_SUCCESS = 'last_auth_success';
    public const LAST_AUTH_FAILURE = 'last_auth_failure';
    public const NB_AUTH_FAILURE = 'nb_auth_failure';
}
