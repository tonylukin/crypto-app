<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Order;
use Symfony\Contracts\EventDispatcher\Event;

class SellOrderCreatedEvent extends Event
{
    public function __construct(
        private Order $order,
    ) {}

    public function getOrder(): Order
    {
        return $this->order;
    }
}
