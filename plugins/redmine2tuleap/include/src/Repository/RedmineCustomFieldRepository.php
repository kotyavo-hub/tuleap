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

    public function allMapped(string $mapBy): array
    {
        $cfs = $this->all();
        return array_map(
            function (array $cf) {
                return $cf[RedmineCustomFieldColumnEnum::ID];
                },
            $cfs
        );
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

    public function get(int $id): array
    {
        return $this->allMapped(RedmineCustomFieldColumnEnum::ID)[$id];
    }

    public function allOfUser(string $remapBy = null): array
    {
        return $this->allOfType(RedmineCustomFieldTypeEnum::USER(), $remapBy);
    }

    public function allOfProject(string $remapBy = null): array
    {
        return $this->allOfType(RedmineCustomFieldTypeEnum::PROJECT(), $remapBy);
    }

    public function allOfIssue(string $remapBy = null): array
    {
        return $this->allOfType(RedmineCustomFieldTypeEnum::ISSUE(), $remapBy);
    }

    public function valuesOfField(int $id): array
    {
        $possibleValues = $this->get($id)[RedmineCustomFieldColumnEnum::POSSIBLE_VALUES];

        $values = [];
        foreach (explode(PHP_EOL, $possibleValues) as $possibleValue) {
            if (($valuePos = strpos($possibleValue, '- ')) !== false) {
                $parsedValue = json_decode(substr($possibleValue, $valuePos + 1), true);
                if ($parsedValue) {
                    $values[] = $parsedValue;
                }
            }
        }

        return $values;
    }
}
