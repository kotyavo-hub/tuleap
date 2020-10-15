<?php

namespace Maximaster\RedmineTuleapImporter\Enum;

use MyCLabs\Enum\Enum;

class RedmineCustomFieldColumnEnum extends Enum
{
    public const ID = 'id';
    public const TYPE = 'type';
    public const NAME = 'name';
    public const FIELD_FORMAT = 'field_format';
    public const POSSIBLE_VALUES = 'possible_values';
    public const REGEXP = 'regexp';
    public const MIN_LENGTH = 'min_length';
    public const MAX_LENGTH = 'max_length';
    public const IS_REQUIRED = 'is_required';
    public const IS_FOR_ALL = 'is_for_all';
    public const IS_FILTER = 'is_filter';
    public const POSITION = 'position';
    public const SEARCHABLE = 'searchable';
    public const DEFAULT_VALUE = 'default_value';
    public const EDITABLE = 'editable';
    public const VISIBLE = 'visible';
    public const MULTIPLE = 'multiple';
    public const FORMAT_STORE = 'format_store';
    public const DESCRIPTION = 'description';
}
