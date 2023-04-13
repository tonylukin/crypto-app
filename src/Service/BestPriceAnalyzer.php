<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Order;
use App\Entity\Symbol;
use App\Entity\UserSymbol;
use App\Repository\PriceRepository;
use Psr\Log\LoggerInterface;

class BestPriceAnalyzer
{
    private const PERCENT_VALUE_FOR_MIN_PRICE_ON_DISTANCE = 12;
    private const MIN_PERCENT_PRICE_DIFFERENCE_FOR_CHANGING_DIRECTION = 0.15;

    private const DIRECTION_PRICE_RISING_UP = 1;
    private const DIRECTION_PRICE_FALLING_DOWN = -1;

    private const PRICE_MOVING_ON_SHORT_INTERVAL = 'Price moving on short interval';
    private const PRICE_RECENTLY_CHANGED_DIRECTION = 'Price recently changed direction';

    private const ITEMS_COUNT_FOR_CHECKING_CHANGED_DIRECTION = 6;

    private ?string $reason = null;
    private ApiInterface $api;

    public function __construct(
        private PriceRepository $priceRepository,
        private LoggerInterface $logger,
    ) {}

    public function setApi(ApiInterface $api): static
    {
        $this->api = $api;

        return $this;
    }

    public function getBestPriceForOrder(UserSymbol $userSymbol): ?float
    {
        // don't set order before some history obtained
        $pricesCount = $this->priceRepository->count(['symbol' => $userSymbol->getSymbol()]);
        if ($pricesCount < $userSymbol->getUser()->getUserSetting()->getMinPricesCountMustHaveBeforeOrder()) {
            return null;
        }

        $currentPrice = $this->api->price($userSymbol->getSymbol()->getName());
        if ($this->isPriceOnFallingDown($userSymbol, $currentPrice)) {
            return null;
        }

        if ($this->isPriceOnMovingRecentlyChangedDirection($userSymbol, $currentPrice, self::DIRECTION_PRICE_FALLING_DOWN)
            || $this->isPriceOnRisingUp($userSymbol, $currentPrice)) {
            return $currentPrice;
        }

        return null;
    }

    public function getBestPriceForSell(UserSymbol $userSymbol): ?float
    {
        $currentPrice = $this->api->price($userSymbol->getSymbol()->getName());

        if ($this->isPriceOnMovingRecentlyChangedDirection($userSymbol, $currentPrice, self::DIRECTION_PRICE_RISING_UP)) {
            return $currentPrice;
        }

        return null;
    }

    /**
     * В отличие от checkDirection() этот метод считает разницу по движению цены
     * @return bool Если true, то direction совпадает с направлением роста/падения валюты
     */
    private function isPriceOnMovingRecentlyChangedDirection(UserSymbol $userSymbol, float $price, int $direction, string $interval = null): bool
    {
        $interval ??= sprintf('PT%dH', $userSymbol->getUser()->getUserSetting()->getHoursExtremelyShortIntervalForPrices());
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
            // первая итерация
            if ($i === 0) {
                $lastPrice = $priceEntity->getPrice();
                continue;
            }

            // промежуточные не интересуют
            if ($i > 0 && $i < self::ITEMS_COUNT_FOR_CHECKING_CHANGED_DIRECTION - 1) {
                continue;
            }

            $currentDiff = $priceEntity->getPrice() - $lastPrice;
            $lastPrice = $priceEntity->getPrice();

            // последняя цена
            // сначала одинаковые знаки у направления и изменения цены, плато тоже подходит
            $result = $currentDiff * $direction >= 0;

            // если падение от цены недостаточное по отношению к максимальной цене за последнее время, то прерываем
            if ($direction === self::DIRECTION_PRICE_FALLING_DOWN) {
                $lastHighPrice = $this->priceRepository->getLastHighPrice(
                    $userSymbol->getSymbol(),
                    new \DateInterval(sprintf('PT%dH', $userSymbol->getUser()->getUserSetting()->getFallenPriceIntervalHours())),
                );
                $lastMinPrice = $this->priceRepository->getLastMinPrice(
                    $userSymbol->getSymbol(),
                    new \DateInterval(sprintf('PT%dH', $userSymbol->getUser()->getUserSetting()->getFallenPriceIntervalHours())),
                );
                $minFallenPricePercent = $userSymbol->getUser()->getUserSetting()->getMinFallenPricePercent();
                // для ситуаций, когда происходит скачок цены, мы пытаемся высчитать коэф-т, который может увеличить наш минимальный процент падения,
                // но только увеличить! В случае если там разница меньше нашего мин. падения, этот коэф-т =1
                $bottomToTopPercent = ($lastHighPrice - $lastMinPrice) / $lastMinPrice * 100;
                // здесь мы смотрим, чтобы наш коэф-т падения (в процентах) не был больше ДВОЙНОГО разрешенного падения
                // двойного потому, что мы смотри различие между низом и верхом. Норму можно получить делением пополам
                $minFallenPricePercent = $bottomToTopPercent > 1.8 * $minFallenPricePercent ? $bottomToTopPercent : $minFallenPricePercent;
                if (($lastHighPrice - $price) / $price * 100 < $minFallenPricePercent) {
                    return false;
                }

                // смотрим, чтобы эти падения не были на самих хаях
                $minDiff = $price - $this->priceRepository->getLastMinPrice(
                    $userSymbol->getSymbol(),
                    new \DateInterval(sprintf('P%dD', $userSymbol->getUser()->getUserSetting()->getDaysIntervalMinPriceOnDistance()))
                );
                if ($minDiff / $price * 100 > self::PERCENT_VALUE_FOR_MIN_PRICE_ON_DISTANCE) {
                    return false;
                }
            }

            $currentDiff = $price - $lastPrice;

            // последняя итерация
            // затем разные
            $result = $result && ($currentDiff * $direction < 0);

            // в случае подъема после падения смотрим, чтобы шаг цены был равномерным, большие скачки ни к чему
            if ($direction === self::DIRECTION_PRICE_FALLING_DOWN
                && (abs($lastPrice - $price) / $price) * 100 > $userSymbol->getUser()->getUserSetting()->getLegalMovingStepPercent()
            ) {
                return false;
            }

            // не нужно считать изменением направления небольшой рост цены
            if ((abs($lastPrice - $price) / $price) * 100 < self::MIN_PERCENT_PRICE_DIFFERENCE_FOR_CHANGING_DIRECTION) {
                return false;
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
                new \DateInterval(sprintf('PT%dH', $userSymbol->getUser()->getUserSetting()->getHoursExtremelyShortIntervalForPrices())),
                $userSymbol->getSymbol()
            )
        ;
        if (!$this->checkDirection($userSymbol, $price, $direction)) {
            return false;
        }

        $diffPercent = abs($price - $avgPriceShortInterval) / $price * 100;
        if ($diffPercent < $userSymbol->getUser()->getUserSetting()->getMaxPercentDiffOnMoving()) {
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
//    private function checkDirection(UserSymbol $userSymbol, float $price, int $direction, string $interval = null): bool
//    {
//        $interval ??= sprintf('PT%dH', $userSymbol->getUser()->getUserSetting()->getHoursExtremelyShortIntervalForPrices());
//        $avgPriceShortInterval = $this
//            ->priceRepository
//            ->getAvgForInterval(
//                new \DateInterval($interval),
//                $userSymbol->getSymbol()
//            )
//        ;
//        return ($price - $avgPriceShortInterval) * $direction > 0;
//    }

    public function getPriceProfit(Order $pendingOrder, float $possibleSalePrice): ?float
    {
        $expenses = $pendingOrder->getPrice() * $pendingOrder->getQuantity();
        $profit = ($possibleSalePrice - $pendingOrder->getPrice()) * $pendingOrder->getQuantity();
        $exchangeFee = $possibleSalePrice * $pendingOrder->getQuantity() * $this->api->getFeeMultiplier(true);
        $profit -= $exchangeFee;

        if ($profit < 0) {
            return null;
        }

        $maxDaysWaitingForProfit = $pendingOrder->getUser()->getUserSetting()->getMaxDaysWaitingForProfit();
        if ($pendingOrder->getCreatedAt()->modify(sprintf('+%d days', $maxDaysWaitingForProfit)) <= new \DateTimeImmutable()) {
            $this->logger->warning("Was waiting too long for profit, sold after {$maxDaysWaitingForProfit} days: {$pendingOrder->getQuantity()} [{$pendingOrder->getSymbol()->getName()}] with profit {$profit}", [
                'user' => $pendingOrder->getUser()->getUserIdentifier(),
            ]);
            return $profit;
        }

        $profitPercent = $profit / $expenses * 100;
        if ($pendingOrder->getCreatedAt()->modify(sprintf('+%d days', ceil($maxDaysWaitingForProfit / 2))) <= new \DateTimeImmutable()
            && $profitPercent >= $pendingOrder->getUser()->getUserSetting()->getMinProfitPercent() / 2) {
            $this->logger->warning("Sold after a half of max interval: {$pendingOrder->getQuantity()} [{$pendingOrder->getSymbol()->getName()}] with profit {$profit}", [
                'user' => $pendingOrder->getUser()->getUserIdentifier(),
            ]);
            return $profit;
        }

        if ($profitPercent < $pendingOrder->getUser()->getUserSetting()->getMinProfitPercent()) {
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

    /**
     * @return bool Если true, то direction совпадает с направлением роста/падения валюты
     */
    private function checkDirection(UserSymbol $userSymbol, float $price, int $direction, string $interval = null): bool
    {
        $interval ??= sprintf('PT%dH', $userSymbol->getUser()->getUserSetting()->getHoursExtremelyShortIntervalForPrices());
        $priceEntities = $this
            ->priceRepository
            ->getLastItemsForInterval(
                new \DateInterval($interval),
                $userSymbol->getSymbol()
            )
        ;
        $lastPrice = $price;
        $positiveDiff = $negativeDiff = 0;
        foreach (array_reverse($priceEntities) as $priceEntity) {
            $currentDiff = $lastPrice - $priceEntity->getPrice();
            $lastPrice = $priceEntity->getPrice();
            if ($currentDiff  > 0) {
                $positiveDiff += $currentDiff;
            } else {
                $negativeDiff += $currentDiff;
            }
        }
        $directionMultiplier = $positiveDiff > abs($negativeDiff);

        return $directionMultiplier * $direction > 0;
    }
}
