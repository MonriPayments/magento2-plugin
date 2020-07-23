<?php

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
