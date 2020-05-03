<?php /** @noinspection SqlResolve */

/** @noinspection SqlNoDataSourceInspection */

namespace LeGAG\Importer;

use Exception;
use PDO;
use Psr\Log\LoggerInterface;
use RuntimeException;

class Importer
{
    protected $dbUser;
    protected $dbPass;
    protected $dbName;
    protected $dbHost;

    /** @var PDO */
    protected $connection;

    /** @var LoggerInterface A PSR3-compatible logger */
    protected $logger;

    /** @var ListTable */
    protected $producers;

    /** @var ListTable */
    protected $packagings;

    /** @var ListTable */
    protected $measuringUnits;

    /** @var ListTable */
    protected $products;

    public function __construct(LoggerInterface $logger, $dbName = 'db', $dbHost = 'db', $dbUser = 'db', $dbPass = 'db')
    {
        $this->logger = $logger;
        $this->dbHost = $dbHost;
        $this->dbUser = $dbUser;
        $this->dbPass = $dbPass;
        $this->dbName = $dbName;

        $this->initConnection();
    }

    public function importFrom($file)
    {
        $fh = fopen($file, 'rb');

        $this->producers = new ListTable($this->connection, 'producteurs', ['raison_sociale'], $this->logger);
        $this->packagings = new ListTable($this->connection, 'conditionnements', ['nom'], $this->logger);
        $this->measuringUnits = new ListTable($this->connection, 'unites', ['nom'], $this->logger);
        $this->products = new ListTable($this->connection, 'produits', ['producteur', 'nom'], $this->logger);

        // Discard the header row
        fgetcsv($fh);

        $productVariantRecords = [];
        while ($record = fgetcsv($fh, 0, ';')) {
            list($productName, $isOrganic, $price, $packaging, $capacity, $capacityUnit, $measuringUnit, $basePrice, $basePriceUnit, , $producer, $category, $animal, $description, $producerLocation) = $record;

            $productVariantRecords[] = [
                'produit'         => $this->products->getId([
                    'producteur' => $this->producers->getId(['raison_sociale' => $producer]),
                    'nom'        => $productName,
                ]),
                'prix'            => (float)$price,
                'conditionnement' => $this->packagings->getId(['nom' => $packaging]),
                'contenance'      => sprintf('%s %s',trim($capacity), trim($capacityUnit)),
                'prix_de_base'    => (float)$basePrice,
                'unite_de_mesure' => $this->measuringUnits->getId(['nom' => $measuringUnit]),
                'date_created'    => strftime('%Y-%m-%d %H:%M:%S'),
            ];
        }

        $this->connection->beginTransaction();
        try {
            $this->executeQueries('producteurs', $this->producers->recordsToCreate);
            $this->executeQueries('produits', $this->products->recordsToCreate);
            $this->executeQueries('unites', $this->measuringUnits->recordsToCreate);
            $this->executeQueries('conditionnements', $this->packagings->recordsToCreate);
            $this->executeQueries('produits_variantes', $productVariantRecords, true);
        } catch (Exception $e) {
            $this->connection->rollBack();
            $this->logger->error('❌ An error occurred during the import process.');
            throw $e;
        }

        $this->logger->info('✅ Import complete');
        $this->connection->commit();
    }

    /**
     * Create the connection to the database
     */
    protected function initConnection()
    {
        try {
            $this->connection = new PDO("mysql:host={$this->dbHost};dbname={$this->dbName}", $this->dbUser, $this->dbPass);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
        } catch (Exception $e) {
            var_dump($e);
            exit;
        }
    }

    protected function debugReport()
    {
        $this->logger->debug('Producers to create: ');
        foreach ($this->producers->recordsToCreate as $id => $record) {
            $this->logger->debug('  * #{id} => {raison_sociale}', array_merge(['id' => $id], $record));
        }
        $this->logger->debug('');

        $this->logger->debug('Packagings to create: ');
        foreach ($this->packagings->recordsToCreate as $id => $record) {
            $this->logger->debug('  * #{id} => {nom}', array_merge(['id' => $id], $record));
        }
        $this->logger->debug('');

        $this->logger->debug('Measuring units to create: ');
        foreach ($this->measuringUnits->recordsToCreate as $id => $record) {
            $this->logger->debug('  * #{id} => {nom}', array_merge(['id' => $id], $record));
        }
        $this->logger->debug('');

        $this->logger->debug('Products to create: ');
        foreach ($this->products->recordsToCreate as $id => $record) {
            $this->logger->debug('  * #{id} => producteur: #{producteur}, nom: {nom}', array_merge(['id' => $id], $record));
        }
        $this->logger->debug('');
    }

    protected function executeQueries(string $table, array $data, $skipIdColumn = false)
    {
        $firstRecord = reset($data);
        $columns = implode(', ', array_keys($firstRecord));
        $questionMarks = implode(', ', array_fill(0, count($firstRecord), '?'));

        if (!$skipIdColumn) {
            $columns = 'id, ' . $columns;
            $questionMarks = '?, ' . $questionMarks;
        }

        $insertProducersStatement = $this->connection->prepare("INSERT INTO $table ($columns) VALUES($questionMarks);");
        foreach ($data as $id => $record) {
            $parameters = array_values($record);
            if (!$skipIdColumn) {
                array_unshift($parameters, $id);
            }

            $isSuccessful = $insertProducersStatement->execute($parameters);
            if (!$isSuccessful) {
                throw new RuntimeException("Cannot insert record #$id in $table table");
            }
        }
    }
}
