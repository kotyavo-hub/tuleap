<?php

namespace Maximaster\Redmine2TuleapPlugin\Framework;

use Exception;
use Maximaster\Redmine2TuleapPlugin\Enum\DatabaseEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\EntityTypeEnum;
use Maximaster\Redmine2TuleapPlugin\Repository\PluginRedmine2TuleapReferenceRepository;
use ParagonIE\EasyDB\EasyDB;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class GenericTransferCommand extends Command
{
    /** @var EasyDB */
    private $redmineDb;

    /** @var EasyDB */
    private $tuleapDb;

    /** @var PluginRedmine2TuleapReferenceRepository */
    private $refRepo;

    abstract protected function entityType(): EntityTypeEnum;

    abstract protected function transfer(InputInterface $input, SymfonyStyle $output): int;

    public function __construct(EasyDB $redmineDb, EasyDB $tuleapDb, PluginRedmine2TuleapReferenceRepository $refRepo)
    {
        parent::__construct();

        $this->redmineDb = $redmineDb;
        $this->tuleapDb = $tuleapDb;
        $this->refRepo = $refRepo;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        return $this->transfer($input, new SymfonyStyle($input, $output));
    }

    /**
     * @param DatabaseEnum $dbName
     *
     * @return EasyDB
     *
     * @throws Exception
     */
    public function db(DatabaseEnum $dbName): EasyDB
    {
        switch ($dbName->getValue()) {
            case DatabaseEnum::REDMINE:
                return $this->redmineDb;

            case DatabaseEnum::TULEAP:
                return $this->tuleapDb;
        }

        throw new Exception(sprintf('Unknown database "%s"', $dbName->getValue()));
    }

    public function redmine(): EasyDB
    {
        return $this->redmineDb;
    }

    public function tuleap(): EasyDB
    {
        return $this->tuleapDb;
    }

    /**
     * @param string $sqlBatch
     *
     * @throws Exception
     */
    public function importSqlBatch(EasyDB $connection, string $sqlBatch): void
    {
        $pdo = $connection->getPdo();
        if ($pdo->exec($sqlBatch) === false) {
            throw new Exception(sprintf('Не удалось провести импорт SQL-пакета "%s": %s', mb_substr($sqlBatch, 0, 100), $pdo->errorInfo()[2]));
        }
    }

    public function markAsTransfered(string $redmineId, string $tuleapId): void
    {
        $this->refRepo->addReference($this->entityType(), $redmineId, $tuleapId);
    }

    public function transferedRedmineIdList(): array
    {
        return $this->refRepo->idsOfType($this->entityType());
    }
}
