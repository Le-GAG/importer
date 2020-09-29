<?php
/**
 * @author Yohann Bianchi<yohann.b@lahautesociete.com>
 * @date   25/06/2020 00:18
 */

namespace LeGAG\Importer;


use LeGAG\Importer\adapters\AdapterInterface;
use LeGAG\Importer\adapters\CsvAdapter;
use LeGAG\Importer\adapters\GoogleSheetsAdapter;
use RuntimeException;
use splitbrain\phpcli\Options;

class AdapterFactory
{
    public static function getAdapter(Options $options): AdapterInterface
    {
        switch ($options->getCmd()) {
            case 'google-sheets':
                return new GoogleSheetsAdapter($options->getArgs()[0], $options->getArgs()[1], $options->getArgs()[2]);
            case 'csv':
                return new CsvAdapter($options->getArgs()[0]);
            default:
                throw new RuntimeException('Not implemented');
        }
    }
}
