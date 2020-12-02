<?php

namespace Maximaster\Redmine2TuleapPlugin\Enum;

use MyCLabs\Enum\Enum;

class TuleapTrackerArtifactColumnEnum extends Enum
{
    public const ID = 'id';
    public const TRACKER_ID = 'tracker_id';
    public const LAST_CHANGESET_ID = 'last_changeset_id';
    public const SUBMITTED_BY = 'submitted_by';
    public const SUBMITTED_ON = 'submitted_on';
    public const USE_ARTIFACT_PERMISSIONS = 'use_artifact_permissions';
    public const PER_TRACKER_ARTIFACT_ID = 'per_tracker_artifact_id';
}
