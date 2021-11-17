<?php

declare(strict_types=1);

namespace App\Config;

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class Config
{
    /** @var Document[] */
    private array $documents;

    public function serialize(string $format = 'json'): string
    {
        return self::getSerializer()->serialize($this, $format, [AbstractNormalizer::IGNORED_ATTRIBUTES => ['query']]);
    }

    public static function deserialize(string $data, string $format = 'json'): Config
    {
        $data = self::getSerializer()->deserialize($data, Config::class, $format);

        return $data;
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

    private static function getSerializer(): Serializer
    {
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];

        return new Serializer($normalizers, $encoders);
    }
}
