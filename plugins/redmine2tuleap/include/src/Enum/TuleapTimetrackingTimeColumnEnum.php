<?php

namespace Maximaster\Redmine2TuleapPlugin\Enum;

use MyCLabs\Enum\Enum;

class TuleapTimetrackingTimeColumnEnum extends Enum
{
    public const ID = 'id';
    public const USER_ID = 'user_id';
    public const ARTIFACT_ID = 'artifact_id';
    public const MINUTES = 'minutes';
    public const STEP = 'step';
    public const DAY = 'day';
}
