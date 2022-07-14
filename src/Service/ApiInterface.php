<?php

declare(strict_types=1);

namespace App\Service;

interface ApiInterface
{
    public function price(string $symbol): float;

    public function buyLimit(string $symbol, float $quantity, float $price): array;

    public function sellLimit(string $symbol, float $quantity, float $price): array;

    public function getFeeMultiplier(): float;
}
