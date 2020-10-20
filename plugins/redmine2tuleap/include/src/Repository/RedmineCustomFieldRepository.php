<?php

namespace Maximaster\Redmine2TuleapPlugin\Repository;

use Maximaster\Redmine2TuleapPlugin\Enum\RedmineCustomFieldColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineCustomFieldTypeEnum;
use ParagonIE\EasyDB\EasyDB;

class RedmineCustomFieldRepository
{
    /** @var array|null */
    private $cache;

    /** @var EasyDB */
    private $connection;

    public function __construct(EasyDB $connection)
    {
        $this->connection = $connection;
    }

    public function all(): array
    {
        $customFields = $this->connection->run('select * from custom_fields');

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
