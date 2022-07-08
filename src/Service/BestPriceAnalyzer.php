<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Order;
use App\Entity\Symbol;
use App\Repository\PriceRepository;
use Psr\Log\LoggerInterface;

class BestPriceAnalyzer
{
    private const HOURS_EXTREMELY_SHORT_INTERVAL_FOR_PRICES = 'PT4H';
    private const DAYS_SHORT_INTERVAL_FOR_PRICES = 'P1D';
    private const MAX_PERCENT_DIFF_ON_MOVING = 3;

    private const DIRECTION_PRICE_RISING_UP = 1;
    private const DIRECTION_PRICE_FALLING_DOWN = -1;

    public function __construct(
        private PriceRepository $priceRepository,
        private ApiInterface $api,
        private LoggerInterface $logger
    ) {
    }

    public function getBestPriceForOrder(Symbol $symbol): ?float
    {
        // don't set order before some history obtained
        $pricesCount = $this->priceRepository->count(['symbol' => $symbol]);
        if ($pricesCount < 6) {
            return null;
        }

        $currentPrice = $this->api->price($symbol->getName());
        if ($this->isPriceOnFallingDown($symbol, $currentPrice)) {
            return null;
        }

        if ($this->isBestPriceForDirectionUntilPlato($symbol, $currentPrice, self::DIRECTION_PRICE_FALLING_DOWN)
            || $this->isPriceOnMovingRecentlyChangedDirection($symbol, $currentPrice, self::DIRECTION_PRICE_FALLING_DOWN)
            || $this->isPriceOnRisingUp($symbol, $currentPrice)) {
            return $currentPrice;
        }

        return null;
    }

    public function getBestPriceForSale(Symbol $symbol): ?float
    {
        $currentPrice = $this->api->price($symbol->getName());

        if ($this->isBestPriceForDirectionUntilPlato($symbol, $currentPrice, self::DIRECTION_PRICE_RISING_UP)
            || $this->isPriceOnMovingRecentlyChangedDirection($symbol, $currentPrice, self::DIRECTION_PRICE_RISING_UP)) {
            return $currentPrice;
        }

        return null;
    }

    private function isBestPriceForDirectionUntilPlato(Symbol $symbol, float $price, int $direction): bool
    {
        $avgPriceShortInterval = $this
            ->priceRepository
            ->getAvgForInterval(
                new \DateInterval(self::DAYS_SHORT_INTERVAL_FOR_PRICES),
                $symbol
            )
        ;

        $diffPercent = abs($price - $avgPriceShortInterval) / $price * 100;
        if ($diffPercent > self::MAX_PERCENT_DIFF_ON_MOVING) {
            // not a plato, rising or failing price
            return false;
        }

        if (!$this->checkDirection($symbol, $price, $direction, self::DAYS_SHORT_INTERVAL_FOR_PRICES)) {
            return false;
        }

        $this->logger->warning("Price on plato: {$price} {$symbol->getName()}", ['direction' => $direction > 0 ? 'rising up' : 'falling down']);
        return true;
    }

    private function isPriceOnRisingUp(Symbol $symbol, float $price): bool
    {
        // todo как-то контролировать, что мы можем упереться в потолок и ставка не будет перебита никогда
        // todo ставить какие-то лимиты на покупку на таких подъемах, т.к. это риск
        return $this->isPriceMovingOnShortInterval($symbol, $price, self::DIRECTION_PRICE_RISING_UP);
    }

    private function isPriceOnFallingDown(Symbol $symbol, float $price): bool
    {
        return $this->isPriceMovingOnShortInterval($symbol, $price, self::DIRECTION_PRICE_FALLING_DOWN);
    }

    private function isPriceMovingOnShortInterval(Symbol $symbol, float $price, int $direction): bool
    {
        if (!$symbol->isRiskable()) {
            $this->logger->warning("Symbol {$symbol->getName()} not risky");
            return false;
        }
        $avgPriceShortInterval = $this
            ->priceRepository
            ->getAvgForInterval(
                new \DateInterval(self::HOURS_EXTREMELY_SHORT_INTERVAL_FOR_PRICES),
                $symbol
            )
        ;
        if (!$this->checkDirection($symbol, $price, $direction)) {
            return false;
        }

        $diffPercent = abs($price - $avgPriceShortInterval) / $price * 100;
        if ($diffPercent < self::MAX_PERCENT_DIFF_ON_MOVING) {
            // very small difference
            return false;
        }

        $this->logger->warning("Price on short interval: {$price} {$symbol->getName()}", ['direction' => $direction > 0 ? 'rising up' : 'falling down']);
        return true;
    }

    private function isPriceOnMovingRecentlyChangedDirection(Symbol $symbol, float $price, int $direction): bool
    {
        $avgPrice = $this
            ->priceRepository
            ->getAvgForInterval(
                new \DateInterval(self::HOURS_EXTREMELY_SHORT_INTERVAL_FOR_PRICES),
                $symbol
            )
        ;
        // смотрим последнюю цену и сравниваем с нашей, одинаковые знаки - значит сменилось направление
        if (($avgPrice - $price) * $direction > 0) {
            $this->logger->warning("Price recently changed direction: {$price} {$symbol->getName()}", ['direction' => $direction > 0 ? 'rising up' : 'falling down']);
            return true;
        }

        return false;
    }

    /**
     * @return bool Если true, то direction совпадает с направлением роста/падения валюты
     */
    private function checkDirection(Symbol $symbol, float $price, int $direction, string $interval = self::HOURS_EXTREMELY_SHORT_INTERVAL_FOR_PRICES): bool
    {
        $avgPriceShortInterval = $this
            ->priceRepository
            ->getAvgForInterval(
                new \DateInterval($interval),
                $symbol
            )
        ;
        return ($avgPriceShortInterval - $price) * $direction < 0;
    }

    public function getPriceProfit(Order $pendingOrder, float $possibleSalePrice): ?float
    {
        $expenses = $pendingOrder->getPrice() * $pendingOrder->getQuantity();
        $profit = ($possibleSalePrice - $pendingOrder->getPrice()) * $pendingOrder->getQuantity();
        $exchangeFee = $possibleSalePrice * $pendingOrder->getQuantity() * $this->api->getFeeMultiplier();
        $profit -= $exchangeFee;

        if ($profit < 0) {
            return null;
        }

        // todo set symbol inactive for this case
        if ($pendingOrder->getCreatedAt()->modify('+2 weeks') <= new \DateTimeImmutable()) {
            return $profit;
        }

        $profitPercent = $profit / $expenses * 100;
        if ($profitPercent < OrderManager::MINIMAL_PROFIT_PERCENT) {
            return null;
        }

        return $profit;
    }
}
