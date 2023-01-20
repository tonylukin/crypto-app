<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Order;
use App\Entity\Symbol;
use App\Entity\UserSymbol;
use App\Repository\PriceRepository;

class BestPriceAnalyzer
{
    private const HOURS_EXTREMELY_SHORT_INTERVAL_FOR_PRICES = 'PT4H';
    private const HOURS_SHORT_INTERVAL_FOR_PRICES_PLATO = 'PT8H';
    private const MAX_PERCENT_DIFF_ON_MOVING = 3;
    private const MIN_PRICES_COUNT_MUST_HAVE_BEFORE_ORDER = 6;

    private const DIRECTION_PRICE_RISING_UP = 1;
    private const DIRECTION_PRICE_FALLING_DOWN = -1;

    private const PRICE_IS_ON_PLATO = 'Price is on plato';
    private const PRICE_MOVING_ON_SHORT_INTERVAL = 'Price moving on short interval';
    private const PRICE_RECENTLY_CHANGED_DIRECTION = 'Price recently changed direction';

    private const LEGAL_STEP_MOVING_PERCENTAGE = 5; // шаг цены за час, который считаем адекватным. шаг больше - это уже резкое падение или рост
    private const LEGAL_FALLEN_PRICE_PERCENTAGE = 4; // разница между максимальным значением за последнее время и текущим при достижении дна
    private const ITEMS_COUNT_FOR_CHECKING_CHANGED_DIRECTION = 3;

    private ?string $reason = null;

    public function __construct(
        private PriceRepository $priceRepository,
        private ApiInterface $api,
    ) {
    }

    public function getBestPriceForOrder(UserSymbol $userSymbol): ?float
    {
        // don't set order before some history obtained
        $pricesCount = $this->priceRepository->count(['symbol' => $userSymbol->getSymbol()]);
        if ($pricesCount < self::MIN_PRICES_COUNT_MUST_HAVE_BEFORE_ORDER) {
            return null;
        }

        $currentPrice = $this->api->price($userSymbol->getSymbol()->getName());
        if ($this->isPriceOnFallingDown($userSymbol, $currentPrice)) {
            return null;
        }

        if ($this->isBestPriceForDirectionUntilPlato($userSymbol, $currentPrice, self::DIRECTION_PRICE_FALLING_DOWN)
            || $this->isPriceOnMovingRecentlyChangedDirection($userSymbol, $currentPrice, self::DIRECTION_PRICE_FALLING_DOWN)
            || $this->isPriceOnRisingUp($userSymbol, $currentPrice)) {
            return $currentPrice;
        }

        return null;
    }

    public function getBestPriceForSell(UserSymbol $userSymbol): ?float
    {
        $currentPrice = $this->api->price($userSymbol->getSymbol()->getName());

        if ($this->isBestPriceForDirectionUntilPlato($userSymbol, $currentPrice, self::DIRECTION_PRICE_RISING_UP)
            || $this->isPriceOnMovingRecentlyChangedDirection($userSymbol, $currentPrice, self::DIRECTION_PRICE_RISING_UP)) {
            return $currentPrice;
        }

        return null;
    }

    /**
     * В отличие от checkDirection() этот метод считает разницу по движению цены
     * @return bool Если true, то direction совпадает с направлением роста/падения валюты
     */
    private function isPriceOnMovingRecentlyChangedDirection(UserSymbol $userSymbol, float $price, int $direction, string $interval = self::HOURS_EXTREMELY_SHORT_INTERVAL_FOR_PRICES): bool
    {
        $priceEntities = $this
            ->priceRepository
            ->getLastItemsForInterval(
                new \DateInterval($interval),
                $userSymbol->getSymbol()
            )
        ;
        if (count($priceEntities) > self::ITEMS_COUNT_FOR_CHECKING_CHANGED_DIRECTION) {
            $priceEntities = array_slice($priceEntities, -1 * self::ITEMS_COUNT_FOR_CHECKING_CHANGED_DIRECTION, self::ITEMS_COUNT_FOR_CHECKING_CHANGED_DIRECTION);
        }

        $lastPrice = null;
        $result = false;
        foreach ($priceEntities as $i => $priceEntity) {
            if ($i === 0) {
                $lastPrice = $priceEntity->getPrice();
                continue;
            }

            $currentDiff = $priceEntity->getPrice() - $lastPrice;

            $lastPrice = $priceEntity->getPrice();
            if ($i === 1) {
                // сначала одинаковые знаки у направления и изменения цены, плато тоже подходит
                $result = $currentDiff * $direction >= 0;

                // если падение от цены недостаточное по отношению к максимальной цене за последнее время, то прерываем
                $highDiff = $this->priceRepository->getLastHighPrice(
                    $userSymbol->getSymbol(),
                    new \DateInterval('PT24H'),
                ) - $price;
                if ($direction === self::DIRECTION_PRICE_FALLING_DOWN && $highDiff / $price * 100 < self::LEGAL_FALLEN_PRICE_PERCENTAGE) {
                    return false;
                }
            }
            if ($i === 2) {
                // затем разные
                $result = $result && ($currentDiff * $direction < 0);

                // в случае подъема после падения смотрим, чтобы шаг цены был равномерным, большие скачки ни к чему
                if ($direction === self::DIRECTION_PRICE_FALLING_DOWN
                    && (abs($lastPrice - $price) / $price) * 100 > self::LEGAL_STEP_MOVING_PERCENTAGE
                ) {
                    return false;
                }
            }
        }

        if ($result) {
            $this->reason = $this->buildReason(self::PRICE_RECENTLY_CHANGED_DIRECTION, $price, $userSymbol->getSymbol(), $direction * -1);
        }
        return $result;
    }

    private function isPriceOnRisingUp(UserSymbol $userSymbol, float $price): bool
    {
        return $this->isPriceMovingOnShortInterval($userSymbol, $price, self::DIRECTION_PRICE_RISING_UP);
    }

    private function isPriceOnFallingDown(UserSymbol $userSymbol, float $price): bool
    {
        return $this->isPriceMovingOnShortInterval($userSymbol, $price, self::DIRECTION_PRICE_FALLING_DOWN);
    }

    private function isPriceMovingOnShortInterval(UserSymbol $userSymbol, float $price, int $direction): bool
    {
        if (!$userSymbol->isRiskable() && $direction === self::DIRECTION_PRICE_RISING_UP) {
            return false;
        }
        $avgPriceShortInterval = $this
            ->priceRepository
            ->getAvgForInterval(
                new \DateInterval(self::HOURS_EXTREMELY_SHORT_INTERVAL_FOR_PRICES),
                $userSymbol->getSymbol()
            )
        ;
        if (!$this->checkDirection($userSymbol, $price, $direction)) {
            return false;
        }

        $diffPercent = abs($price - $avgPriceShortInterval) / $price * 100;
        if ($diffPercent < self::MAX_PERCENT_DIFF_ON_MOVING) {
            // very small difference
            return false;
        }

        // записываем причину только для повышения (они же только рисковые), поскольку тут произойдет покупка
        if ($direction === self::DIRECTION_PRICE_RISING_UP) {
            $this->reason = $this->buildReason(self::PRICE_MOVING_ON_SHORT_INTERVAL, $price, $userSymbol->getSymbol(), $direction);
        }
        return true;
    }

    /**
     * @return bool Если true, то direction совпадает с направлением роста/падения валюты
     */
    private function checkDirection(UserSymbol $userSymbol, float $price, int $direction, string $interval = self::HOURS_EXTREMELY_SHORT_INTERVAL_FOR_PRICES): bool
    {
        $avgPriceShortInterval = $this
            ->priceRepository
            ->getAvgForInterval(
                new \DateInterval($interval),
                $userSymbol->getSymbol()
            )
        ;
        return ($price - $avgPriceShortInterval) * $direction > 0;
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

        $profitPercent = $profit / $expenses * 100;
        if ($profitPercent < OrderManager::MINIMAL_PROFIT_PERCENT) {
            return null;
        }

        return $profit;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    private function buildReason(string $reason, float $price, Symbol $symbol, int $direction): string
    {
        return \sprintf('%s: %s %s [%s]', $reason, $price, $symbol->getName(), $direction > 0 ? 'rising up' : 'falling down');
    }

    private function isBestPriceForDirectionUntilPlato(UserSymbol $userSymbol, float $price, int $direction): bool
    {
        // todo maybe plato is not needed, work only with changed directions
        return false;

        $avgPriceShortInterval = $this
            ->priceRepository
            ->getAvgForInterval(
                new \DateInterval(self::HOURS_SHORT_INTERVAL_FOR_PRICES_PLATO),
                $userSymbol
            )
        ;

        $diffPercent = abs($price - $avgPriceShortInterval) / $price * 100;
        if ($diffPercent > self::MAX_PERCENT_DIFF_ON_MOVING) {
            // not a plato, rising or failing price
            return false;
        }

        if (!$this->checkDirection($userSymbol, $price, $direction, self::HOURS_SHORT_INTERVAL_FOR_PRICES_PLATO)) {
            return false;
        }

        $this->reason = $this->buildReason(self::PRICE_IS_ON_PLATO, $price, $userSymbol, $direction);
        return true;
    }

    /**
     * @return bool Если true, то direction совпадает с направлением роста/падения валюты
     */
//    private function checkDirection(Symbol $symbol, float $price, int $direction, string $interval = self::HOURS_EXTREMELY_SHORT_INTERVAL_FOR_PRICES): bool
//    {
//        $priceEntities = $this
//            ->priceRepository
//            ->getLastItemsForInterval(
//                new \DateInterval($interval),
//                $symbol
//            )
//        ;
//        $lastPrice = $price;
//        $positiveDiff = $negativeDiff = 0;
//        foreach ($priceEntities as $priceEntity) {
//            $currentDiff = $lastPrice - $priceEntity->getPrice();
//            $lastPrice = $priceEntity->getPrice();
//            if ($currentDiff  > 0) {
//                $positiveDiff += $currentDiff;
//            } else {
//                $negativeDiff += $currentDiff;
//            }
//        }
//        $directionMultiplier = $positiveDiff > abs($negativeDiff);
//
//        return $directionMultiplier * $direction > 0;
//    }
}
