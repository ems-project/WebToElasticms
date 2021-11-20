<?php

namespace App\Helper;

use EMS\CommonBundle\Elasticsearch\Document\Document;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class ExpressionData
{
    /**
     * @var mixed[]
     */
    private array $data;
    private PropertyAccessor $propertyAccessor;

    /**
     * @param array<mixed> $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
        $this->propertyAccessor = new PropertyAccessor();
    }

    /**
     * @param mixed|null $default
     *
     * @return mixed|null
     */
    public function get(string $path, $default = null)
    {
        $property = Document::fieldPathToPropertyPath($path);
        if ($this->propertyAccessor->isReadable($this->data, $property)) {
            return $this->propertyAccessor->getValue($this->data, $property);
        }

        return $default;
    }
}
