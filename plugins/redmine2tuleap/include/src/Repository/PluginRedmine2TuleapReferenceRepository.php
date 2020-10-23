<?php

namespace Maximaster\Redmine2TuleapPlugin\Repository;

use Exception;
use Maximaster\Redmine2TuleapPlugin\Enum\EntityTypeEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\Redmine2TuleapEntityExternalIdColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\TuleapTableEnum;
use ParagonIE\EasyDB\EasyDB;

class PluginRedmine2TuleapReferenceRepository
{
    /** @var EasyDB */
    private $tuleapDb;

    /** @var array */
    private $references;

    public function __construct(EasyDB $tuleapDb)
    {
        $this->tuleapDb = $tuleapDb;
        $this->references = array_fill_keys(array_map('strval', EntityTypeEnum::values()), []);
    }

    public function addReference(EntityTypeEnum $entityType, string $redmineId, string $tuleapId): void
    {
        $entityTypeName = (string) $entityType;

        $marked = $this->tuleapDb->insert(TuleapTableEnum::PLUGIN_REDMINE2TULEAP_ENTITY_EXTERNAL_ID, [
            Redmine2TuleapEntityExternalIdColumnEnum::TYPE => $entityTypeName,
            Redmine2TuleapEntityExternalIdColumnEnum::REDMINE_ID => $redmineId,
            Redmine2TuleapEntityExternalIdColumnEnum::TULEAP_ID => $tuleapId,
        ]);

        if (!$marked) {
            throw new Exception(sprintf('Failed to mark %s %s (%s) as transfered: %d %d %s', $entityType->getValue(), $redmineId, $tuleapId, ...$this->tuleapDb->errorInfo()));
        }

        $this->references[$entityTypeName][$redmineId] = $tuleapId;
    }

    public function findTuleapId(EntityTypeEnum $entityType, string $redmineId): ?string
    {
        $entityTypeName = (string) $entityType;

        if (!isset($this->references[$entityTypeName][$redmineId])) {
            $this->references[$entityTypeName][$redmineId] = $this->tuleapDb->cell('
                SELECT ' . Redmine2TuleapEntityExternalIdColumnEnum::TULEAP_ID . '
                FROM ' . TuleapTableEnum::PLUGIN_REDMINE2TULEAP_ENTITY_EXTERNAL_ID . '
                WHERE
                    ' . Redmine2TuleapEntityExternalIdColumnEnum::TYPE . ' = ? and
                    ' . Redmine2TuleapEntityExternalIdColumnEnum::REDMINE_ID . ' = ?
            ', $entityTypeName, $redmineId);
        }

        return $this->references[$entityTypeName][$redmineId] ?? null;
    }
}
