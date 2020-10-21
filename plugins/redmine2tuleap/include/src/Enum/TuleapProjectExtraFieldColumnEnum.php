<?php

namespace Maximaster\Redmine2TuleapPlugin\Enum;

use MyCLabs\Enum\Enum;

/**
 * @see group_desc table
 */
class TuleapProjectExtraFieldColumnEnum extends Enum
{
    public const GROUP_DESC_ID = 'group_desc_id';
    public const DESC_REQUIRED = 'desc_required';
    public const DESC_NAME = 'desc_name';
    public const DESC_DESCRIPTION = 'desc_description';
    public const DESC_RANK = 'desc_rank';
    public const DESC_TYPE = 'desc_type';
}
