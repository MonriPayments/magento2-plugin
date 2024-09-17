<?php

namespace Monri\Payments\Gateway\Helper;

class CcTypeMapper
{
    public const CC_TYPE_MAP = [
        'AMEX' => 'AE',
        'DINERS' => 'DN',
        'MASTERCARD' => 'MC',
        'VISA' => 'VI',
        'DISCOVER' => 'DI',
        'MAESTRO' => 'MI',
        'VISA PREMIUM'  => 'VP',
        'MVISA' => 'VI' // got this in testing environment?
    ];

    public const CC_TYPE_OTHER = 'OT';

    public const CC_TYPE_NAME_MAP = [
        'AE' => 'American Express',
        'VI' => 'Visa',
        'MC' => 'MasterCard',
        'DI' => 'Discover',
        'MI' => 'Maestro',
        'DN' => 'Diners'
    ];

    /**
     * Coverts WSPay cc type from response to Magento cc type id
     *
     * @param string $type
     * @param string|null $partner
     * @return string
     */
    public function getCcTypeId($type, $partner = null)
    {
        $type = strtoupper($type);
        return self::CC_TYPE_MAP[$type] ?? self::CC_TYPE_OTHER;
    }

    /**
     * Converts Magento type id to display name
     *
     * @param string $typeId
     * @return string
     */
    public function getCcTypeName(string $typeId): string
    {
        return self::CC_TYPE_NAME_MAP[strtoupper($typeId)] ?? $typeId;
    }
}
