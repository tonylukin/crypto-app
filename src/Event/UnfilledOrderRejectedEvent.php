<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Order;

class UnfilledOrderRejectedEvent
{
    public function __construct(
        private Order $order,
    ) {}

    public function getOrder(): Order
    {
        return $this->order;
    }
}
