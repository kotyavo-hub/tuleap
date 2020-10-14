<?php

namespace Maximaster\RedmineTuleapImporter\Database;

use Maximaster\RedmineTuleapImporter\Exception\QueryException;
use mysqli;
use mysqli_result;

class Connection
{
    /** @var mysqli */
    private $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param string $database
     *
     * @throws QueryException
     */
    public function useDatabase(string $database): void
    {
        if (!$this->connection->select_db($database)) {
            throw new QueryException(sprintf('Не удалось подключиться к базе данных "%s"', $database));
        }
    }

    /**
     * @param string $query
     * @param int $resultMode
     *
     * @return mysqli_result
     *
     * @throws QueryException
     */
    public function query(string $query, int $resultMode = MYSQLI_STORE_RESULT): mysqli_result
    {
        $result = $this->connection->query($query, $resultMode);
        if ($result === false) {
            throw new QueryException(sprintf('Не удалось выполнить запрос "%s"', $query));
        }


        return $result;
    }

    public function importDump(string $dump, bool $skipResults = true)
    {
        if (!$this->connection->multi_query($dump)) {
            throw new QueryException(sprintf('Не удалось выполнить серию запросов "%s"', mb_substr($dump, 0, 100) . '...'));
        }

        if ($skipResults) {
            $this->skipResults();
        }
    }

    public function skipResults(): void
    {
        while ($this->connection->more_results()) {
            $this->connection->next_result();
        }
    }
}
