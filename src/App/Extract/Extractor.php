<?php

declare(strict_types=1);

namespace App\Extract;

use App\Cache\CacheManager;
use App\Config\Computer;
use App\Config\ConfigManager;
use App\Config\WebResource;
use App\Helper\ExpressionData;
use App\Rapport\Rapport;
use EMS\CommonBundle\Common\Standard\Json;
use EMS\CommonBundle\Elasticsearch\Document\Document;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ExpressionLanguage;
use Symfony\Component\PropertyAccess\PropertyAccess;

class Extractor
{
    private ConfigManager $config;
    private CacheManager $cache;
    private ExpressionLanguage $expressionLanguage;
    private LoggerInterface $logger;
    private Rapport $rapport;

    public function __construct(ConfigManager $config, CacheManager $cache, LoggerInterface $logger, Rapport $rapport)
    {
        $this->config = $config;
        $this->cache = $cache;
        $this->logger = $logger;
        $this->rapport = $rapport;
        $this->expressionLanguage = $config->getExpressionLanguage();
    }

    public function extractDataCount(): int
    {
        return \count($this->config->getDocuments());
    }

    public function currentStep(): int
    {
        $lastUpdated = $this->config->getLastUpdated();
        if (null === $lastUpdated) {
            return 0;
        }
        $count = 1;
        foreach ($this->config->getDocuments() as $document) {
            if ($document->getOuuid() === $lastUpdated) {
                return $count;
            }
            ++$count;
        }

        return 0;
    }

    /**
     * @return iterable<ExtractedData>
     */
    public function extractData(Rapport $rapport): iterable
    {
        $lastUpdated = $this->config->getLastUpdated();
        $found = (null === $lastUpdated);
        foreach ($this->config->getDocuments() as $document) {
            if (!$found) {
                $found = ($document->getOuuid() === $lastUpdated);
                continue;
            }
            $defaultData = $document->getDefaultData();
            $data = $defaultData;
            foreach ($document->getResources() as $resource) {
                $this->logger->notice(\sprintf('Start extracting from %s', $resource->getUrl()));
                try {
                    $this->extractDataFromResource($document, $resource, $data);
                } catch (ClientException $e) {
                    $rapport->addResourceInError($resource, $e->getCode(), $e->getMessage());
                } catch (RequestException $e) {
                    $rapport->addResourceInError($resource, $e->getCode(), $e->getMessage());
                }
            }

            if ($data === $defaultData) {
                $rapport->addNothingExtracted($document);
                continue;
            }

            $hash = \sha1(Json::encode($data));

            $type = $this->config->getType($document->getType());
            foreach ($type->getComputers() as $computer) {
                if (!$this->condition($computer, $data)) {
                    continue;
                }
                $value = $this->compute($computer, $data);
                $this->assignComputedProperty($computer, $data, $value);
            }

            foreach ($type->getTempFields() as $tempFields) {
                if (isset($data[$tempFields])) {
                    unset($data[$tempFields]);
                }
            }

            $data[$this->config->getHashResourcesField()] = $hash;

            yield new ExtractedData($document, $data);
        }
    }

    /**
     * @param array<mixed> $data
     */
    private function extractDataFromResource(\App\Config\Document $document, WebResource $resource, array &$data): void
    {
        $result = $this->cache->get($resource->getUrl());
        $analyzer = $this->config->getAnalyzer($resource->getType());
        switch ($analyzer->getType()) {
            case Html::TYPE:
                $extractor = new Html($this->config, $document, $this->logger, $this->rapport);
                break;
            default:
                throw new \RuntimeException(\sprintf('Type of analyzer %s unknown', $analyzer->getType()));
        }
        $extractor->extractData($resource, $result, $analyzer, $data);
    }

    /**
     * @param array<mixed> $data
     */
    private function condition(Computer $computer, array &$data): bool
    {
        $condition = $this->expressionLanguage->evaluate($computer->getCondition(), $context = [
            'data' => new ExpressionData($data),
        ]);
        if (!\is_bool($condition)) {
            throw new \RuntimeException(\sprintf('Condition "%s" must return a boolean', $computer->getCondition()));
        }

        return $condition;
    }

    /**
     * @param array<mixed> $data
     *
     * @return mixed
     */
    private function compute(Computer $computer, array &$data)
    {
        $value = $this->expressionLanguage->evaluate($computer->getExpression(), $context = [
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

    public function reset(): void
    {
        $this->config->setLastUpdated(null);
    }
}
