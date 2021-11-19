<?php

declare(strict_types=1);

namespace App\Config;

use App\Cache\Cache;
use EMS\CommonBundle\Common\CoreApi\CoreApi;
use EMS\CommonBundle\Storage\StorageManager;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class Config
{
    /** @var Document[] */
    private array $documents;

    /** @var Analyzer[] */
    private array $analyzers;

    /** @var string[] */
    private array $hosts = [];

    /** @var string[] */
    private $validClasses = [];
    private StorageManager $storageManager;
    private Cache $cache;
    private CoreApi $coreApi;

    public function serialize(string $format = JsonEncoder::FORMAT): string
    {
        return self::getSerializer()->serialize($this, $format);
    }

    public static function deserialize(string $data, string $format = JsonEncoder::FORMAT): Config
    {
        return self::getSerializer()->deserialize($data, Config::class, $format);
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
            new JsonEncoder(),
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
        return $this->hosts;
    }

    /**
     * @param string[] $hosts
     */
    public function setHosts(array $hosts): void
    {
        $this->hosts = $hosts;
    }

    public function findInternalLink(string $path, ?string $fragment = null, ?string $query = null): string
    {
        $path = 'ems://object:page:ouuid';
        if (null !== $fragment) {
            $path .= '#'.$fragment;
        }
        if (null !== $query) {
            $path .= '?'.$query;
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

    public function specifyStorageManager(StorageManager $storageManager): void
    {
        $this->storageManager = $storageManager;
    }

    public function specifyCacheManager(Cache $cache): void
    {
        $this->cache = $cache;
    }

    public function specifyCoreClientManager(CoreApi $coreApi): void
    {
        $this->coreApi = $coreApi;
    }
}
