<?php

namespace Monri\Payments\Gateway\Http\Serializer;

use Magento\Framework\Convert\ConvertArray;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Simplexml\Element;
use Magento\Framework\Simplexml\ElementFactory;

class Xml implements SerializerInterface
{
    /**
     * @var ElementFactory
     */
    private $simpleXmlElementFactory;

    /**
     * @var ConvertArray
     */
    private $convertArray;

    public function __construct(
        ElementFactory $simpleXmlElementFactory,
        ConvertArray $convertArray
    ) {
        $this->simpleXmlElementFactory = $simpleXmlElementFactory;
        $this->convertArray = $convertArray;
    }

    /**
     * Serialize data into string
     *
     * @param string|int|float|bool|array|null $data
     * @return string|bool
     * @throws \InvalidArgumentException
     * @since 101.0.0
     */
    public function serialize($data)
    {
        try {
            $rootNode = array_key_first($data);
            return $this->convertArray->assocToXml($data[$rootNode], $rootNode)->asXML();
        } catch (LocalizedException $e) {
            throw new \InvalidArgumentException('Could not convert data to XML', 0, $e);
        }
    }

    /**
     * Unserialize the given string
     *
     * @param string $string
     * @return string|int|float|bool|array|null
     * @throws \InvalidArgumentException
     * @since 101.0.0
     */
    public function unserialize($string)
    {
        /** @var Element $simpleXmlElement */
        $simpleXmlElement = $this->simpleXmlElementFactory->create([
            'data' => $string
        ]);

        return $this->normalizeArray($simpleXmlElement->asArray());
    }

    protected function normalizeArray(array $array)
    {
        $normalizedArray = [];

        foreach ($array as $key => $value) {
            $normalizedArray[str_replace('-', '_', $key)] = $value;
        }

        return $normalizedArray;
    }
}
