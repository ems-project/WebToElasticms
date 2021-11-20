<?php

declare(strict_types=1);

namespace App\Config;

use App\Cache\CacheManager;
use App\Helper\Url;
use EMS\CommonBundle\Common\CoreApi\CoreApi;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ExpressionLanguage;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class ConfigManager
{
    /** @var Document[] */
    private array $documents;

    /** @var Analyzer[] */
    private array $analyzers;

    /** @var Type[] */
    private array $types;

    /** @var string[] */
    private array $hosts = [];

    /** @var string[] */
    private $validClasses = [];
    /** @var string[] */
    private $linkToClean = [];
    private CacheManager $cacheManager;
    private CoreApi $coreApi;
    private LoggerInterface $logger;
    private ?ExpressionLanguage $expressionLanguage = null;
    private string $hashResourcesField = 'import_hash_resources';

    public function serialize(string $format = JsonEncoder::FORMAT): string
    {
        return self::getSerializer()->serialize($this, $format);
    }

    public static function deserialize(string $data, CacheManager $cache, CoreApi $coreApi, LoggerInterface $logger, string $format = JsonEncoder::FORMAT): ConfigManager
    {
        $config = self::getSerializer()->deserialize($data, ConfigManager::class, $format);
        $config->cacheManager = $cache;
        $config->coreApi = $coreApi;
        $config->logger = $logger;

        return $config;
    }

    private static function getSerializer(): Serializer
    {
        $reflectionExtractor = new ReflectionExtractor();
        $phpDocExtractor = new PhpDocExtractor();
        $propertyTypeExtractor = new PropertyInfoExtractor([$reflectionExtractor], [$phpDocExtractor, $reflectionExtractor], [$phpDocExtractor], [$reflectionExtractor], [$reflectionExtractor]);

        return new Serializer([
            new ArrayDenormalizer(),
            new ObjectNormalizer(null, null, null, $propertyTypeExtractor),
        ], [
            new XmlEncoder(),
            new JsonEncoder(new JsonEncode([JsonEncode::OPTIONS => JSON_PRETTY_PRINT]), null),
        ]);
    }

    /**
     * @return Document[]
     */
    public function getDocuments(): array
    {
        return $this->documents;
    }

    /**
     * @param Document[] $documents
     */
    public function setDocuments(array $documents): void
    {
        $this->documents = $documents;
    }

    /**
     * @return Analyzer[]
     */
    public function getAnalyzers(): array
    {
        return $this->analyzers;
    }

    /**
     * @param Analyzer[] $analyzers
     */
    public function setAnalyzers(array $analyzers): void
    {
        $this->analyzers = $analyzers;
    }

    public function getAnalyzer(string $analyzerName): Analyzer
    {
        foreach ($this->analyzers as $analyzer) {
            if ($analyzer->getName() === $analyzerName) {
                return $analyzer;
            }
        }

        throw new \RuntimeException(\sprintf('Analyzer %s not found', $analyzerName));
    }

    /**
     * @return string[]
     */
    public function getHosts(): array
    {
        if (empty($this->hosts)) {
            foreach ($this->documents as $document) {
                foreach ($document->getResources() as $resource) {
                    $url = new Url($resource->getUrl());
                    if (!\in_array($url->getHost(), $this->hosts)) {
                        $this->hosts[] = $url->getHost();
                    }
                }
            }
        }

        return $this->hosts;
    }

    /**
     * @param string[] $hosts
     */
    public function setHosts(array $hosts): void
    {
        $this->hosts = $hosts;
    }

    public function findInternalLink(Url $url): string
    {
        $path = $this->findInDocuments($url);
        if (null === $path && null === $url->getFragment() && null === $url->getQuery()) {
            $path = $this->downloadAsset($url);
        }
        if (null === $path) {
            $path = $url->getPath();
            $this->logger->warning(\sprintf('It was not possible to convert the path %s', $path));
        }

        if (null !== $url->getFragment()) {
            $path .= '#'.$url->getFragment();
        }
        if (null !== $url->getQuery()) {
            $path .= '?'.$url->getQuery();
        }

        return $path;
    }

    /**
     * @return string[]
     */
    public function getValidClasses(): array
    {
        return $this->validClasses;
    }

    /**
     * @param string[] $validClasses
     */
    public function setValidClasses(array $validClasses): void
    {
        $this->validClasses = $validClasses;
    }

    private function findInDocuments(Url $url): ?string
    {
        foreach ($this->documents as $document) {
            $ouuid = $document->getOuuid();
            if (null === $ouuid) {
                continue;
            }
            foreach ($document->getResources() as $resource) {
                $resourceUrl = new Url($resource->getUrl());
                if ($resourceUrl->getPath() === $url->getPath()) {
                    return \sprintf('ems://object:%s:%s', $document->getType(), $ouuid);
                }
            }
        }

        return null;
    }

    private function downloadAsset(Url $url): ?string
    {
        $asset = $this->cacheManager->get($url->getUrl());
        $mimeType = $asset->getMimetype();
        if (false !== \strpos($mimeType, 'text/html')) {
            return null;
        }
        $filename = $url->getFilename();
        $hash = $this->coreApi->file()->uploadStream($asset->getStream(), $filename, $mimeType);

        return \sprintf('ems://asset:%s?name=%s&type=%s', $hash, \urlencode($filename), \urlencode($mimeType));
    }

    /**
     * @return string[]
     */
    public function getLinkToClean(): array
    {
        return $this->linkToClean;
    }

    /**
     * @param string[] $linkToClean
     */
    public function setLinkToClean(array $linkToClean): void
    {
        $this->linkToClean = $linkToClean;
    }

    /**
     * @return Type[]
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * @param Type[] $types
     */
    public function setTypes(array $types): void
    {
        $this->types = $types;
    }

    public function getType(string $name): Type
    {
        foreach ($this->types as $type) {
            if ($type->getName() === $name) {
                return $type;
            }
        }

        throw new \RuntimeException(\sprintf('Type %s not found', $name));
    }

    public function save(string $jsonPath): bool
    {
        return false !== \file_put_contents($jsonPath, $this->serialize());
    }

    public function getExpressionLanguage(): ExpressionLanguage
    {
        if (null !== $this->expressionLanguage) {
            return $this->expressionLanguage;
        }
        $this->expressionLanguage = new ExpressionLanguage();

        $this->expressionLanguage->register('uuid', function () {
            return '(\\Ramsey\\Uuid\\Uuid::uuid4()->toString())';
        }, function ($arguments) {
            return \Ramsey\Uuid\Uuid::uuid4()->toString();
        });

        $this->expressionLanguage->register('json_escape', function ($str) {
            return \sprintf('(null === %1$s ? null : \\EMS\\CommonBundle\\Common\\Standard\\Json::escape(%1$s))', $str);
        }, function ($arguments, $str) {
            return null === $str ? null : \EMS\CommonBundle\Common\Standard\Json::escape($str);
        });

        return $this->expressionLanguage;
    }

    public function getHashResourcesField(): string
    {
        return $this->hashResourcesField;
    }

    public function setHashResourcesField(string $hashResourcesField): void
    {
        $this->hashResourcesField = $hashResourcesField;
    }
}
