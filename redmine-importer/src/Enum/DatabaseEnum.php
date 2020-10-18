<?php

namespace Maximaster\RedmineTuleapImporter\Enum;

use MyCLabs\Enum\Enum;

/**
 * @method static self REDMINE()
 * @method static self TULEAP()
 */
class DatabaseEnum extends Enum
{
    public const REDMINE = 'redmine';
    public const TULEAP = 'tuleap';

    public static function DEFAULT(): self
    {
        return self::TULEAP();
    }
}
