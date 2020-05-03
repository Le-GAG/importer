<?php /** @noinspection SqlNoDataSourceInspection, SqlResolve */

namespace LeGAG\Importer;

use PDO;
use Psr\Log\LoggerInterface;

/**
 * Represents a database list table.
 * Used to keep track of existing records and records to be created.
 */
class ListTable
{
    /** @var PDO */
    protected $connection;

    /** @var array  */
    public $existingRecords = [];

    /** @var array  */
    public $recordsToCreate = [];

    /** @var string[] */
    protected $columns;

    /** @var string */
    protected $table;

    /** @var LoggerInterface A PSR3-compatible logger */
    protected $logger;

    public function __construct(PDO $connection, string $tableName, array $columns, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
        $this->table = $tableName;
        $this->columns = $columns;

        $statement = $this->connection->query(sprintf(
            "SELECT id, %s FROM $tableName ORDER BY id ASC;",
            implode(',', $columns)
        ));

        $this->logger->debug('Existing {table}: ', ['table' => $tableName]);
        foreach ($statement->fetchAll() as $record) {
            $this->existingRecords[(int)$record->id] = array_filter(
                (array)$record,
                function($column) { return $column !== 'id'; },
                ARRAY_FILTER_USE_KEY
            );
            $this->logger->debug('  * #{id} => {array}', [
                'id' => $record->id,
                'array' => preg_replace(
                    ["/\n([()])\n/", '/ +/', "/\n */"],
                    [' $1 ', ' ', ', '],
                    print_r($this->existingRecords[(int)$record->id], true)
                ),
            ]);
        }
        $this->logger->debug('');
    }

    /**
     * Return the id of an existing producer or add it to the records to create and return the new id
     * @param string[] $record
     * @return int
     */
    public function getId(array $record): int
    {
        $combinedRecords = $this->getCombinedRecords();

        if ($id = array_search($record, $combinedRecords, true)) {
            return $id;
        }

        $id = max(array_keys($combinedRecords)) + 1;
        $this->recordsToCreate[$id] = $record;

        return $id;
    }

    protected function getCombinedRecords(): array
    {
        /** @noinspection AdditionOperationOnArraysInspection */
        return $this->recordsToCreate + $this->existingRecords;
    }
}
