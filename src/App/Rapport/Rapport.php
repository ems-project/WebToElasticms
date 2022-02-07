<?php

namespace App\Rapport;

use EMS\CommonBundle\Common\SpreadsheetGeneratorService;

class Rapport
{
    private array $urlsInError = [];
    private array $missingInternalUrls = [];

    public function __construct(string $folder)
    {
        $this->filename = $folder.DIRECTORY_SEPARATOR.\sprintf('WebToElasticms-Rapport-%s.xlsx', date("Ymd-His"));
        $this->spreadsheetGeneratorService = new SpreadsheetGeneratorService();
    }

    public function save(): void
    {
        $config = [
            SpreadsheetGeneratorService::WRITER => SpreadsheetGeneratorService::XLSX_WRITER,
            SpreadsheetGeneratorService::CONTENT_FILENAME => 'WebToElasticms-Rapport.xlsx',
            SpreadsheetGeneratorService::SHEETS => [
                [
                    'name' => 'url-in-error',
                    'rows' => [
                        ['URLs']
                    ]
                ],
                [
                    'name' => 'missing-internal-urls',
                    'rows' => [
                        ['Path', 'Referrer', 'URLs'],
                    ]
                ],
                [
                    'name' => 'accessor-errors',
                    'rows' => [
                        ['Type', 'OUUID', 'accessor', 'message', 'Admin URLs'],
                    ]
                ],
            ],
        ];
        $this->spreadsheetGeneratorService->generateSpreadsheetFile($config, $this->filename);
    }

}