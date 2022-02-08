<?php

namespace App\Rapport;

use App\Cache\CacheManager;
use App\Config\Document;
use App\Config\Extractor;
use App\Config\WebResource;
use App\Helper\Url;
use EMS\CommonBundle\Common\SpreadsheetGeneratorService;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;

class Rapport
{
    /** @var string[][] */
    private array $missingInternalUrls = [['Path', 'URL', 'Code', 'Message', 'Referrers']];
    /** @var string[][] */
    private array $nothingExtracted = [['Type', 'OUUID', 'URLs']];
    /** @var string[][] */
    private array $extractErrors = [['Type', 'URL', 'Locale', 'Selector', 'Strategy', 'Property', 'Attribute', 'Count']];
    /** @var string[][] */
    private array $urlsInError = [['Doc\'s URLs', 'URLs', 'Code', 'Message']];
    private string $filename;
    private SpreadsheetGeneratorService $spreadsheetGeneratorService;
    private CacheManager $cacheManager;

    public function __construct(CacheManager $cacheManager, string $folder)
    {
        $this->filename = $folder.DIRECTORY_SEPARATOR.\sprintf('WebToElasticms-Rapport-%s.xlsx', \date('Ymd-His'));
        $this->spreadsheetGeneratorService = new SpreadsheetGeneratorService();
        $this->cacheManager = $cacheManager;
    }

    public function save(): void
    {
        $config = [
            SpreadsheetGeneratorService::WRITER => SpreadsheetGeneratorService::XLSX_WRITER,
            SpreadsheetGeneratorService::CONTENT_FILENAME => 'WebToElasticms-Rapport.xlsx',
            SpreadsheetGeneratorService::SHEETS => [
                [
                    'name' => 'URLs in error',
                    'rows' => \array_values($this->urlsInError),
                ],
                [
                    'name' => 'Broken internal links',
                    'rows' => \array_values($this->missingInternalUrls),
                ],
                [
                    'name' => 'Extract errors',
                    'rows' => \array_values($this->extractErrors),
                ],
                [
                    'name' => 'Nothing extracted',
                    'rows' => \array_values($this->nothingExtracted),
                ],
            ],
        ];
        $this->spreadsheetGeneratorService->generateSpreadsheetFile($config, $this->filename);
    }

    public function inUrlsNotFounds(Url $url): bool
    {
        if (!isset($this->missingInternalUrls[$url->getPath()])) {
            return false;
        }
        $this->missingInternalUrls[$url->getPath()][] = $url->getReferer();

        return true;
    }

    public function addUrlNotFound(Url $url): void
    {
        try {
            $result = $this->cacheManager->head($url->getUrl());
            $code = $result->getResponse()->getStatusCode();
            $message = '';
        } catch (ClientException $e) {
            $message = $e->getMessage();
            $response = $e->getResponse();
            if (null === $response) {
                $code = 0;
            } else {
                $code = $response->getStatusCode();
            }
        } catch (RequestException $e) {
            $message = $e->getMessage();
            $response = $e->getResponse();
            if (null === $response) {
                $code = 0;
            } else {
                $code = $response->getStatusCode();
            }
        }
        $this->missingInternalUrls[$url->getPath()] = [$url->getPath(), $url->getUrl(), \strval($code), $message, $url->getReferer() ?? 'N/A'];
    }

    public function addResourceInError(WebResource $resource, Url $url, int $code, string $message): void
    {
        $this->urlsInError[] = [$resource->getUrl(), $url->getUrl(), \strval($code), $message];
    }

    public function addNothingExtracted(Document $document): void
    {
        $data = [
            $document->getType(),
            $document->getOuuid(),
        ];
        foreach ($document->getResources() as $resource) {
            $data[] = $resource->getUrl();
        }

        $this->nothingExtracted[] = $data;
    }

    public function addExtractError(WebResource $resource, Extractor $extractor, int $count): void
    {
        $this->extractErrors[] = [$resource->getType(), $resource->getUrl(), $resource->getLocale(), $extractor->getSelector(), $extractor->getStrategy(), $extractor->getProperty(), $extractor->getAttribute() ?? '', \strval($count)];
    }
}
