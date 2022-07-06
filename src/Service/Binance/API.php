<?php

declare(strict_types=1);

namespace App\Service\Binance;

use App\Service\ApiInterface;

class API extends \Binance\API implements ApiInterface
{
    private const FEE_PERCENT = 0.1;

    public function __construct(string $apiKey, string $apiSecret)
    {
        parent::__construct($apiKey, $apiSecret);
        $this->caOverride = true;
    }

    public function price(string $symbol): float
    {
        return (float) parent::price($symbol);
    }

    public function buyLimit(string $symbol, float $quantity, float $price)
    {
//        return parent::buyTest($symbol, (string) $quantity, (string) $price); // todo remove
//        return parent::buy($symbol, (string) $quantity, (string) $price);
    }

    public function sellLimit(string $symbol, float $quantity, float $price)
    {
//        return parent::sellTest($symbol, (string) $quantity, (string) $price); // todo remove
//        return parent::sell($symbol, (string) $quantity, (string) $price);
    }

    public function getFeeMultiplier(): float
    {
        return self::FEE_PERCENT / 100;
    }
}
