<?php
/**
 * @author Yohann Bianchi<yohann.b@lahautesociete.com>
 * @date   25/06/2020 00:00
 */

namespace LeGAG\Importer\adapters;


interface AdapterInterface
{
    /**
     * Return the next row as an array of cells
     * @return string[]|null The next row as an array or null if there are no more rows
     */
    public function next(): ?array;

    /**
     * Reset the adapter internal pointer
     * @return void
     */
    public function rewind(): void;
}
