<?php

namespace Maximaster\Redmine2TuleapPlugin\Enum;

use MyCLabs\Enum\Enum;

class RedmineCustomFieldFormatEnum extends Enum
{
    public const DATE = 'date';
    public const TEXT = 'text';
    public const BOOL = 'bool';
    public const USER = 'user';
    public const FLOAT = 'float';
    public const LIST = 'list';
    public const ENUMERATION = 'enumeration';
    public const LINK = 'link';
    public const STRING = 'string';
    public const INT = 'int';
}
