<?php

namespace LeGAG\Importer;

use Exception;
use InvalidArgumentException;
use PDO;
use splitbrain\phpcli\PSR3CLI;
use splitbrain\phpcli\Options;

class Application extends PSR3CLI
{
    /**
     * @inheritDoc
     */
    protected function setup(Options $options)
    {
        $options->setHelp("A tool to import products (and their related producers, categories…) in the database of le GAG's website");
        $options->registerOption('host', 'Database host', 'h', 'host');
        $options->registerOption('user', 'Database user', 'u', 'user');
        $options->registerOption('password', 'Database password', 'p', 'pass');
        $options->registerOption('db', 'Database name', 'd', 'database');

        $options->registerCommand('csv', 'Import from a CSV file.');
        $options->registerArgument('FILE', 'The CSV file to import', true, 'csv');

        $options->registerCommand('google-sheets', 'Import from a Google Sheet.');
        $options->registerArgument('ID', 'The identifier of the Google Sheet to import from.', true, 'google-sheets');
        $options->registerArgument('RANGE', 'The range of cells to import. The sheet name is usually a good choice.', true, 'google-sheets');
        $options->registerArgument('CREDENTIALS_FILE', 'A JSON file containing the credentials to access the Google Sheet. Provided by Google upon service account creation.', true, 'google-sheets');
    }

    /**
     * @inheritDoc
     */
    protected function main(Options $options)
    {
        $args = $options->getArgs();
        $csvFile = reset($args);

        $connection = $this->getDbConnection(
            $options->getOpt('db', 'db'),
            $options->getOpt('host', 'db'),
            $options->getOpt('user', 'db'),
            $options->getOpt('password', 'db')
        );

        $importer = new Importer($this, $connection, AdapterFactory::getAdapter($options));

        $importer->import();
    }

    /**
     * Create the connection to the database
     */
    protected function getDbConnection(string $name, string $host, string $user, string $pass)
    {
        try {
            $connection = new PDO("mysql:host={$host};dbname={$name}", $user, $pass);
            $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

            return $connection;
        } catch (Exception $e) {
            var_dump($e);
            exit;
        }
    }
}
