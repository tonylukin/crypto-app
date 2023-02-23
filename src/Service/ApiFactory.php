<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Order;
use App\Service\Binance\API as BinanceApi;
use App\Service\Huobi\API as HuobiApi;

class ApiFactory
{
    public function __construct(
        private BinanceApi $binanceApi,
        private HuobiApi $huobiApi,
    ) {}

    public function build(int $exchange): ApiInterface
    {
        return match ($exchange) {
            Order::EXCHANGE_BINANCE => $this->binanceApi,
            Order::EXCHANGE_HUOBI => $this->huobiApi,
            default => throw new \InvalidArgumentException("Incorrect exchange: {$exchange}"),
        };
    }
}
