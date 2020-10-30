<?php

namespace Maximaster\Redmine2TuleapPlugin\Repository;

use Exception;
use Maximaster\Redmine2TuleapPlugin\Config\TransferConfig;
use Maximaster\Redmine2TuleapPlugin\Enum\EntityTypeEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\Redmine2TuleapEntityExternalIdColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\TuleapTableEnum;
use ParagonIE\EasyDB\EasyDB;

class PluginRedmine2TuleapReferenceRepository
{
    /** @var EasyDB */
    private $tuleapDb;

    /** @var TransferConfig */
    private $config;

    /** @var array */
    private $references;

    /** @var EntityTypeEnum[] */
    private $types;

    public function __construct(EasyDB $tuleapDb)
    {
        $this->tuleapDb = $tuleapDb;
        $this->references = array_fill_keys(array_map('strval', EntityTypeEnum::values()), []);
        $this->types = array_combine(EntityTypeEnum::toArray(), EntityTypeEnum::values());
    }

    public function setConfig(TransferConfig $config): void
    {
        $this->config = $config;
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

    public function findTuleapId(EntityTypeEnum $entityType, string $redmineId, bool $useConfigFallback = false): ?string
    {
        $entityTypeName = (string) $entityType;

        if (!isset($this->references[$entityTypeName][$redmineId])) {
            $tuleapId = $this->tuleapDb->cell('
                SELECT ' . Redmine2TuleapEntityExternalIdColumnEnum::TULEAP_ID . '
                FROM ' . TuleapTableEnum::PLUGIN_REDMINE2TULEAP_ENTITY_EXTERNAL_ID . '
                WHERE
                    ' . Redmine2TuleapEntityExternalIdColumnEnum::TYPE . ' = ? and
                    ' . Redmine2TuleapEntityExternalIdColumnEnum::REDMINE_ID . ' = ?
            ', $entityTypeName, $redmineId);

            if (!$tuleapId
                && $useConfigFallback === true
                && $this->config
                && $entityType->equals(EntityTypeEnum::USER())
            ) {
                $tuleapId = $this->findTuleapId($entityType, $this->config->defaultRedmineUserId());
            }

            $this->references[$entityTypeName][$redmineId] = $tuleapId;
        }

        return $this->references[$entityTypeName][$redmineId] ?? null;
    }

    public function getTuleapId(EntityTypeEnum $entityType, string $redmineId, bool $useConfigFallback = false): ?string
    {
        $tuleapId = $this->findTuleapId($entityType, $redmineId, $useConfigFallback);
        if (!$tuleapId) {
            throw new Exception(sprintf('Failed to find tuleap id for remdmine object %s#%d', $entityType->getValue(), $redmineId));
        }

        return $tuleapId;
    }

    public function idsOfType(EntityTypeEnum $entityType)
    {
        return $this->tuleapDb->column(
            '
                SELECT ' . Redmine2TuleapEntityExternalIdColumnEnum::REDMINE_ID . '
                FROM ' .  TuleapTableEnum::PLUGIN_REDMINE2TULEAP_ENTITY_EXTERNAL_ID . '
                WHERE
                    ' . Redmine2TuleapEntityExternalIdColumnEnum::TYPE . ' = ?
            ',
            [ $entityType->getValue() ]
        );
    }

    public function clear(): void
    {
        $this->tuleapDb->run('DELETE FROM ' . TuleapTableEnum::PLUGIN_REDMINE2TULEAP_ENTITY_EXTERNAL_ID);
    }

    public function getTuleapUserId(string $redmineId, bool $useConfigFallback = false): string
    {
        return $this->getTuleapId($this->types[EntityTypeEnum::USER], $redmineId, $useConfigFallback);
    }

    public function getTuleapProjectId(string $redmineId, bool $useConfigFallback = false): string
    {
        return $this->getTuleapId($this->types[EntityTypeEnum::PROJECT], $redmineId, $useConfigFallback);
    }

    public function getArtifactId(string $issueId, bool $useConfigFallback = false): string
    {
        return $this->getTuleapId($this->types[EntityTypeEnum::ISSUE], $issueId, $useConfigFallback);
    }
}
