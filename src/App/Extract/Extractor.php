<?php

declare(strict_types=1);

namespace App\Extract;

use App\Cache\CacheManager;
use App\Config\Computer;
use App\Config\ConfigManager;
use App\Config\WebResource;
use App\Helper\ExpressionData;
use EMS\CommonBundle\Common\Standard\Json;
use EMS\CommonBundle\Elasticsearch\Document\Document;
use Symfony\Component\DependencyInjection\ExpressionLanguage;
use Symfony\Component\PropertyAccess\PropertyAccess;

class Extractor
{
    private ConfigManager $config;
    private CacheManager $cache;
    private ExpressionLanguage $expressionLanguage;

    public function __construct(ConfigManager $config, CacheManager $cache)
    {
        $this->config = $config;
        $this->cache = $cache;
        $this->expressionLanguage = $config->getExpressionLanguage();
    }

    public function extractDataCount(): int
    {
        return \count($this->config->getDocuments());
    }

    /**
     * @return iterable<ExtractedData>
     */
    public function extractData(): iterable
    {
        foreach ($this->config->getDocuments() as $document) {
            $hash = \hash_init('sha1');
            $data = [];
            foreach ($document->getResources() as $resource) {
                $this->extractDataFromResource($resource, $data);
                $this->hashUpdate($hash, $resource);
            }

            $type = $this->config->getType($document->getType());
            foreach ($type->getComputers() as $computer) {
                $value = $this->compute($computer, $data);
                $this->assignComputedProperty($computer, $data, $value);
            }

            foreach ($type->getTempFields() as $tempFields) {
                if (isset($data[$tempFields])) {
                    unset($data[$tempFields]);
                }
            }

            $data[$this->config->getHashResourcesField()] = \hash_final($hash);

            yield new ExtractedData($document, $data);
        }
    }

    /**
     * @param array<mixed> $data
     */
    private function extractDataFromResource(WebResource $resource, array &$data): void
    {
        $result = $this->cache->get($resource->getUrl());
        $analyzer = $this->config->getAnalyzer($resource->getType());
        switch ($analyzer->getType()) {
            case Html::TYPE:
                $extractor = new Html($this->config);
                break;
            default:
                throw new \RuntimeException(\sprintf('Type of analyzer %s unknown', $analyzer->getType()));
        }
        $extractor->extractData($resource, $result, $analyzer, $data);
    }

    /**
     * @param array<mixed> $data
     *
     * @return mixed
     */
    private function compute(Computer $computer, array &$data)
    {
        $value = $this->expressionLanguage->evaluate($computer->getExpression(), [
            'data' => new ExpressionData($data),
        ]);

        if ($computer->isJsonDecode() && \is_string($value)) {
            if ('null' === \trim($value)) {
                return null;
            }

            return Json::decode($value);
        }

        return $value;
    }

    /**
     * @param array<mixed>             $data
     * @param string|array<mixed>|null $value
     */
    private function assignComputedProperty(Computer $computer, array &$data, $value): void
    {
        $property = Document::fieldPathToPropertyPath($computer->getProperty());
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $propertyAccessor->setValue($data, $property, $value);
    }

    private function hashUpdate(\HashContext $hash, WebResource $resource): void
    {
        $stream = $this->cache->get($resource->getUrl())->getStream();
        while (!$stream->eof()) {
            \hash_update($hash, $stream->read(819200));
        }
    }
}
