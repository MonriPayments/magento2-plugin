<?php

namespace Monri\Payments\Model\ResourceModel;

use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Magento\Framework\Exception\LocalizedException;

class GetOrderIdByIncrement
{
    /**
     * @var OrderResource
     */
    private $orderResource;

    /**
     * GetOrderIdByIncrement constructor.
     *
     * @param OrderResource $orderResource
     */
    public function __construct(
        OrderResource $orderResource
    ) {
        $this->orderResource = $orderResource;
    }

    /**
     * @param string $incrementId
     * @return int
     */
    public function execute(string $incrementId): int
    {
        try {
            $connection = $this->orderResource->getConnection();
            $table = $this->orderResource->getMainTable();

            $select = $connection->select()
                ->from($table, [$this->orderResource->getIdFieldName()])
                ->where('increment_id = ?', $incrementId)
                ->limit(1);

            return (int)$connection->fetchOne($select);
        } catch (LocalizedException $e) {
            return 0;
        }
    }
}
