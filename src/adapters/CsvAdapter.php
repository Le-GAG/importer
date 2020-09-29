<?php
/**
 * @author Yohann Bianchi<yohann.b@lahautesociete.com>
 * @date   25/06/2020 00:09
 */

namespace LeGAG\Importer\adapters;

class CsvAdapter implements AdapterInterface
{
    /** @var false|resource */
    protected $fileHandle;

    public function __construct($filename)
    {
        $this->fileHandle = fopen($filename, 'rb');

        // Discard the header row
        fgetcsv($this->fileHandle);
    }

    /**
     * @inheritDoc
     */
    public function next(): ?array
    {
        return fgetcsv($this->fileHandle, 0, ';') ?: null;
    }

    /**
     * @inheritDoc
     */
    public function rewind(): void
    {
        rewind($this->fileHandle);

        // Discard the header row
        fgetcsv($this->fileHandle);
    }
}
