<?php

/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */

namespace Monri\Payments\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;

class GetOrderIdByIncrement
{
    /**
     * @var OrderResource
     */
    private $orderResource;

    public function __construct(
        OrderResource $orderResource
    ) {
        $this->orderResource = $orderResource;
    }

    /**
     * Get order ID by increment ID.
     *
     * @param $incrementId
     * @return int
     * @throws LocalizedException
     */
    public function execute($incrementId)
    {
        $connection = $this->orderResource->getConnection();
        $table = $this->orderResource->getMainTable();

        $select = $connection->select()
            ->from($table, [$this->orderResource->getIdFieldName()])
            ->where('increment_id = ?', $incrementId)
            ->limit(1);

        return (int)$connection->fetchOne($select);
    }
}
