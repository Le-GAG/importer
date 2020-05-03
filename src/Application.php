<?php

namespace LeGAG\Importer;

use InvalidArgumentException;
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
        $options->registerArgument('FILE', 'The CSV file to import');
        $options->registerOption('host', 'Database host', 'h', 'host');
        $options->registerOption('user', 'Database user', 'u', 'user');
        $options->registerOption('password', 'Database password', 'p', 'pass');
        $options->registerOption('db', 'Database name', 'd', 'database');
    }

    /**
     * @inheritDoc
     */
    protected function main(Options $options)
    {
        $csvFile = $options->getArgs()[0];

        if (!is_file($csvFile)) {
            throw new InvalidArgumentException('Argument 1 must be the path to a CSV file.');
        }

        $importer = new Importer(
            $this,
            $options->getOpt('db', 'db'),
            $options->getOpt('host', 'db'),
            $options->getOpt('user', 'db'),
            $options->getOpt('password', 'db')
        );

        $importer->importFrom($csvFile);
    }
}
