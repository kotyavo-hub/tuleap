<?php

namespace Maximaster\Redmine2TuleapPlugin\Repository;

use ParagonIE\EasyDB\EasyDB;

class PluginRedmine2TuleapTrackerFieldListBindUsersBackupRepository
{
    public const TABLE = 'plugin_redmine2tuleap_tracker_field_list_bind_users_backup';

    /** @var EasyDB */
    private $tuleapDb;

    public function __construct(EasyDB $tuleapDb)
    {
        $this->tuleapDb = $tuleapDb;
    }

    public function clear(): void
    {
        $this->tuleapDb->run('TRUNCATE ' . self::TABLE);
    }

    public function backupField(int $fieldId, string $valueFunction): int
    {
        return $this->tuleapDb->insert(self::TABLE, [
            'field_id' => $fieldId,
            'value_function' => $valueFunction,
        ]);
    }

    public function all(): array
    {
        return $this->tuleapDb->run('SELECT * FROM ' . self::TABLE);
    }
}
