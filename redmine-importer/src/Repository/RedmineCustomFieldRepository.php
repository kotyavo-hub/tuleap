<?php

namespace Maximaster\RedmineTuleapImporter\Repository;

use Maximaster\RedmineTuleapImporter\Enum\RedmineCustomFieldColumnEnum;
use Maximaster\RedmineTuleapImporter\Enum\RedmineCustomFieldTypeEnum;
use MysqliDb;

class RedmineCustomFieldRepository
{
    /** @var array|null */
    private $cache;

    /** @var MysqliDb */
    private $connection;

    public function __construct(MysqliDb $connection)
    {
        $this->connection = $connection;
    }

    public function all(): array
    {
        $customFields = $this->connection->query('select * from custom_fields');

        if ($this->cache) {
            return $this->cache;
        }

        $this->cache = $customFields;
        return $customFields;
    }

    public function allOfType(RedmineCustomFieldTypeEnum $type, string $remapBy = null): array
    {
        $customFields = array_filter($this->all(), function (array $customField) use ($type) {
            return $customField[RedmineCustomFieldColumnEnum::TYPE] === $type->getValue();
        });

        if ($remapBy !== null) {
            $customFields = array_combine(
                array_map(
                    function (array $customField) use ($remapBy) {
                        return $customField[$remapBy];
                    },
                    $customFields
                ),
                $customFields
            );
        }

        return $customFields;
    }

    public function allOfUser(string $remapBy = null): array
    {
        return $this->allOfType(RedmineCustomFieldTypeEnum::USER(), $remapBy);
    }
}
