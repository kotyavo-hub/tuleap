<?php

namespace Maximaster\Redmine2TuleapPlugin\Repository;

use Maximaster\Redmine2TuleapPlugin\Enum\RedmineCustomFieldColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineCustomFieldTypeEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineTableEnum;
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
        if ($this->cache !== null) {
            return $this->cache;
        }

        $customFields = $this->connection->run('select * from ' . RedmineTableEnum::CUSTOM_FIELDS);
        $this->cache = $customFields;

        return $this->cache;

    }

    public function allMapped(RedmineCustomFieldColumnEnum $mapField): array
    {
        $mapFieldName = $mapField->getValue();

        $cfs = $this->all();

        return array_combine(
            array_map(
                function (array $cf) use ($mapFieldName) {
                    return $cf[$mapFieldName];
                },
                $cfs
            ),
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
        return $this->allMapped(RedmineCustomFieldColumnEnum::ID())[$id];
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
