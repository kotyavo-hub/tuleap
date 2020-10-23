<?php

namespace Maximaster\Redmine2TuleapPlugin\Enum;

use MyCLabs\Enum\Enum;

class TuleapTrackerFieldColumnEnum extends Enum
{
    public const ID = 'id';
    public const OLD_ID = 'old_id';
    public const TRACKER_ID = 'tracker_id';
    public const PARENT_ID = 'parent_id';
    public const FORMELEMENT_TYPE = 'formElement_type';
    public const NAME = 'name';
    public const LABEL = 'label';
    public const DESCRIPTION = 'description';
    public const USE_IT = 'use_it';
    public const RANK = 'rank';
    public const SCOPE = 'scope';
    public const REQUIRED = 'required';
    public const NOTIFICATIONS = 'notifications';
    public const ORIGINAL_FIELD_ID = 'original_field_id';
}
