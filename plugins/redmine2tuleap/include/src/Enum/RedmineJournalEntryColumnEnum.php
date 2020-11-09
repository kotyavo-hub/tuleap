<?php

namespace Maximaster\Redmine2TuleapPlugin\Enum;

use MyCLabs\Enum\Enum;

class RedmineJournalEntryColumnEnum extends Enum
{
    public const ID = 'id';
    public const JOURNALIZED_ID = 'journalized_id';
    public const JOURNALIZED_TYPE = 'journalized_type';
    public const USER_ID = 'user_id';
    public const NOTES = 'notes';
    public const CREATED_ON = 'created_on';
    public const PRIVATE_NOTES = 'private_notes';
    public const SYNCHRONY_ID = 'synchrony_id';
}
