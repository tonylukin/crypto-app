<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Order;

class PartialFilledOrderFoundEvent
{
    public function __construct(
        private Order $order,
        private float $partialQuantity,
    ) {}

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function getPartialQuantity(): float
    {
        return $this->partialQuantity;
    }
}
