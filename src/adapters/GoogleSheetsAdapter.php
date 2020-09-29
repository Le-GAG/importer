<?php
/**
 * @author Yohann Bianchi<yohann.b@lahautesociete.com>
 * @date   25/06/2020 00:31
 */

namespace LeGAG\Importer\adapters;

use Google_Client;
use Google_Service_Sheets;
use Google_Service_Sheets_ValueRange;

class GoogleSheetsAdapter implements AdapterInterface
{
    /** @var Google_Service_Sheets_ValueRange */
    protected $valueRange;

    public function __construct(string $id, string $range, string $credentialsPath)
    {
        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $credentialsPath);
        $client = new Google_Client();
        $client->useApplicationDefaultCredentials();
        $client->addScope(Google_Service_Sheets::SPREADSHEETS_READONLY);
        $service = new Google_Service_Sheets($client);

        $this->valueRange = $service->spreadsheets_values->get($id, $range);
    }

    /**
     * @inheritDoc
     */
    public function next(): ?array
    {
        $next = $this->valueRange->next();
        return $next ?: null;
    }

    /**
     * @inheritDoc
     */
    public function rewind(): void
    {
        $this->valueRange->rewind();
    }
}
