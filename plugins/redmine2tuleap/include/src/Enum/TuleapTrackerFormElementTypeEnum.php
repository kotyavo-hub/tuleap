<?php

namespace Maximaster\Redmine2TuleapPlugin\Enum;

use MyCLabs\Enum\Enum;
use Tracker_FormElement_Field_ArtifactLink;
use Tracker_FormElement_Field_List_Bind_Users;
use Tracker_FormElementFactory;

class TuleapTrackerFormElementTypeEnum extends Enum
{
    public const STRING           = Tracker_FormElementFactory::FIELD_STRING_TYPE;
    public const TEXT             = Tracker_FormElementFactory::FIELD_TEXT_TYPE;
    public const FLOAT            = Tracker_FormElementFactory::FIELD_FLOAT_TYPE;
    public const DATE             = Tracker_FormElementFactory::FIELD_DATE_TYPE;
    public const LAST_UPDATE_DATE = Tracker_FormElementFactory::FIELD_LAST_UPDATE_DATE_TYPE;
    public const SUBMITTED_ON     = Tracker_FormElementFactory::FIELD_SUBMITTED_ON_TYPE;
    public const SUBMITTED_BY     = Tracker_FormElementFactory::FIELD_SUBMITTED_BY_TYPE;
    public const ARTIFACT_ID      = Tracker_FormElementFactory::FIELD_ARTIFACT_ID_TYPE;
    public const SELECT_BOX       = Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE;
    public const RADIO_BUTTON     = Tracker_FormElementFactory::FIELD_RADIO_BUTTON_TYPE;
    public const MULTI_SELECT_BOX = Tracker_FormElementFactory::FIELD_MULTI_SELECT_BOX_TYPE;
    public const FILE             = Tracker_FormElementFactory::FIELD_FILE_TYPE;

    public const INT              = 'int';

    public const ARTIFACT_LINK    = Tracker_FormElement_Field_ArtifactLink::TYPE;

    public const BIND_USERS       = Tracker_FormElement_Field_List_Bind_Users::TYPE;
}
