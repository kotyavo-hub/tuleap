<?php

namespace Maximaster\Redmine2TuleapPlugin\Enum;

use MyCLabs\Enum\Enum;

class TuleapServiceColumnEnum extends Enum
{
    public const SERVICE_ID = 'service_id';
    public const GROUP_ID = 'group_id';
    public const LABEL = 'label';
    public const DESCRIPTION = 'description';
    public const SHORT_NAME = 'short_name';
    public const LINK = 'link';
    public const IS_ACTIVE = 'is_active';
    public const IS_USED = 'is_used';
    public const SCOPE = 'scope';
    public const RANK = 'rank';
    public const LOCATION = 'location';
    public const SERVER_ID = 'server_id';
    public const IS_IN_IFRAME = 'is_in_iframe';
    public const IS_IN_NEW_TAB = 'is_in_new_tab';
    public const ICON = 'icon';
}
