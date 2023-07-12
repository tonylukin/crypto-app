<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\LastPrice;
use App\Entity\Order;
use App\Entity\Symbol;
use App\Entity\UserSymbol;
use App\Repository\LastPriceRepository;
use App\Repository\PriceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class BestPriceAnalyzer
{
    private const MIN_PERCENT_PRICE_DIFFERENCE_FOR_CHANGING_DIRECTION = 0.15;
    // todo to ADDITIONAL settings
    private const MAX_PERCENT_PRICE_DIFFERENCE_AFTER_LAST_PRICE_CANDIDATE = 1;
    private const MAX_PERCENT_PRICE_DIFFERENCE_AFTER_LAST_MIN_PRICE_CANDIDATE = 3;
    private const MAX_HOURS_LAST_PRICE_LIFETIME = 7 * 24;

    private const DIRECTION_PRICE_RISING_UP = 1;
    private const DIRECTION_PRICE_FALLING_DOWN = -1;

    private const PRICE_RECENTLY_CHANGED_DIRECTION = 'Price recently changed direction';

    private const ITEMS_COUNT_FOR_CHECKING_CHANGED_DIRECTION = 6;
    private const PERIOD_MINUTES_MULTIPLIER = 1.3; // важнейший коэффициент при определении периода графика

    private ?string $reason = null;
    private \DateTimeImmutable $currentDateTime;

    public function __construct(
        private PriceRepository $priceRepository,
        private LoggerInterface $logger,
        private LastPriceRepository $lastPriceRepository,
        private EntityManagerInterface $entityManager,
    ) {
        $this->currentDateTime = new \DateTimeImmutable();
    }

    /** for test purpose only */
    public function setCurrentDateTime(\DateTimeImmutable $currentDateTime): BestPriceAnalyzer
    {
        $this->currentDateTime = $currentDateTime;
        return $this;
    }

    /**
     * @return null|array{0: float, 1: LastPrice}
     */
    public function getBestPriceForOrder(UserSymbol $userSymbol, float $currentPrice): ?array
    {
        // don't set order before some history obtained
        $pricesCount = $this->priceRepository->count(['symbol' => $userSymbol->getSymbol()]);
        if ($pricesCount < $userSymbol->getUser()->getUserSetting()->getMinPricesCountMustHaveBeforeOrder()) {
            return null;
        }

        $lastPrice = $this->lastPriceRepository->findOneBy([
            'user' => $userSymbol->getUser(),
            'symbol' => $userSymbol->getSymbol(),
            'lowest' => true,
        ]);

        if ($lastPrice !== null && $lastPrice->getUpdatedAt()->modify(sprintf('+%d minutes', max($lastPrice->getPeriodMinutes(), 12 * 60))) <= $this->currentDateTime) {
            if (
                (($lastPrice->getMaxPrice() - $currentPrice) / $currentPrice * 100 > $userSymbol->getUser()->getUserSetting()->getMinFallenPricePercent())
                &&
                (
                    abs($lastPrice->getPrice() - $currentPrice) / $currentPrice * 100 < self::MAX_PERCENT_PRICE_DIFFERENCE_AFTER_LAST_PRICE_CANDIDATE
                    ||
                    abs($lastPrice->getMinPrice() - $currentPrice) / $currentPrice * 100 < self::MAX_PERCENT_PRICE_DIFFERENCE_AFTER_LAST_MIN_PRICE_CANDIDATE
                )
            ) {
                return [$currentPrice, $lastPrice];
            }

            // если не выгорело, начинаем сначала
            $lastPrice
                ->setPrice($currentPrice)
                ->setUpdatedAt($this->currentDateTime)
                ->increaseAttempt()
            ;
            $this->entityManager->flush();
            return null;
        }

        // должно быть изменение цены, тогда это вариант с дном
        if ($this->compareDirectionWithLastPrice($userSymbol, $currentPrice, self::DIRECTION_PRICE_FALLING_DOWN)) {
            // экспериментальная фича с определением максимума
            if ($lastPrice !== null && bccomp((string) $currentPrice, (string) $lastPrice->getMaxPrice(), 6) > 0) {
                $lastPrice
                    ->setMaxPrice($currentPrice)
//                    ->setUpdatedAt($this->currentDateTime)
                ;
                $this->entityManager->flush();
            }
            if ($lastPrice !== null && bccomp((string) $currentPrice, (string) $lastPrice->getMinPrice(), 6) < 0) {
                $lastPrice
                    ->setMinPrice($currentPrice)
                    ->setUpdatedAt($this->currentDateTime)
                ;
                $this->calculatePeriodMinutes($lastPrice, $this->currentDateTime);
                $this->entityManager->flush();
            }

            return null;
        }

        if ($lastPrice === null) {
            $lastPrice = (new LastPrice())
                ->setUser($userSymbol->getUser())
                ->setSymbol($userSymbol->getSymbol())
                ->setLowest(true)
                ->setPrice($currentPrice)
                ->setMinPrice($currentPrice)
                ->setMaxPrice($currentPrice)
                ->setCreatedAt($this->currentDateTime)
                ->setUpdatedAt($this->currentDateTime)
                ->setPeriodDate($this->currentDateTime)
            ;
            $this->entityManager->persist($lastPrice);
            $this->entityManager->flush();
            return null;
        }

        if ($lastPrice->getUpdatedAt()->modify(sprintf('+%d hours', self::MAX_HOURS_LAST_PRICE_LIFETIME)) <= $this->currentDateTime) {
            $this->entityManager->remove($lastPrice);
            $this->entityManager->flush();
            return null;
        }

        if (bccomp((string) $currentPrice, (string) $lastPrice->getPrice(), 6) < 0) {
            // риск скатывания монеты по примеру LUNA, кроме того никакой логики остановки во время падения
//            if (
//                ($lastPrice->getMaxPrice() - $currentPrice) / $currentPrice * 100 > $userSymbol->getUser()->getUserSetting()->getMinFallenPricePercent() * 2
//                &&
//                ($lastPrice->getMinPrice() - $currentPrice) / $currentPrice * 100 < self::MAX_PERCENT_PRICE_DIFFERENCE_AFTER_LAST_MIN_PRICE_CANDIDATE
//            ) {
//                return [$currentPrice, $lastPrice];
//            }

            $lastPrice
                ->setPrice($currentPrice)
//                ->setUpdatedAt($this->currentDateTime)
                ->increaseAttempt()
            ;
        }

        $this->entityManager->flush();
        return null;
    }

    // true - совпадает
    private function compareDirectionWithLastPrice(UserSymbol $userSymbol, float $price, int $direction): bool
    {
        $priceEntity = $this->priceRepository->getLastItem($userSymbol->getSymbol(), $this->currentDateTime);
        return ($price - $priceEntity?->getPrice()) * $direction > 0;
    }

    public function getBestPriceForSell(UserSymbol $userSymbol, float $currentPrice): ?float
    {
        // todo выносим логику выше в метод и используем здесь
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
                $userSymbol->getSymbol(),
                currentDateTime: $this->currentDateTime,
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
//                $userSymbol->getSymbol(),
//                currentDateTime: $this->currentDateTime,
//            )
//        ;
//        return ($price - $avgPriceShortInterval) * $direction > 0;
//    }

    public function getPriceProfit(Order $pendingOrder, float $possibleSalePrice, float $feeMultiplier): ?float
    {
        $expenses = $pendingOrder->getPrice() * $pendingOrder->getQuantity();
        $profit = ($possibleSalePrice - $pendingOrder->getPrice()) * $pendingOrder->getQuantity();
        $exchangeFee = $possibleSalePrice * $pendingOrder->getQuantity() * $feeMultiplier;
        $profit -= $exchangeFee;

        if ($profit < 0) {
            return null;
        }

        $maxDaysWaitingForProfit = $pendingOrder->getUser()->getUserSetting()->getMaxDaysWaitingForProfit();
        if ($pendingOrder->getCreatedAt()->modify(sprintf('+%d days', $maxDaysWaitingForProfit)) <= $this->currentDateTime) {
            $this->logger->warning("Was waiting too long for profit, sold after {$maxDaysWaitingForProfit} days: {$pendingOrder->getQuantity()} [{$pendingOrder->getSymbol()->getName()}] with profit {$profit}", [
                'user' => $pendingOrder->getUser()->getUserIdentifier(),
            ]);
            return $profit;
        }

        $profitPercent = $profit / $expenses * 100;
        if ($pendingOrder->getCreatedAt()->modify(sprintf('+%d days', ceil($maxDaysWaitingForProfit / 2))) <= $this->currentDateTime
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
                $userSymbol->getSymbol(),
                currentDateTime: $this->currentDateTime,
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

    private function calculatePeriodMinutes(LastPrice $lastPrice, \DateTimeImmutable $currentDateTime): void
    {
        $periodMinutes = $lastPrice->getPeriodDate() !== null ? (int) floor(($currentDateTime->getTimestamp() - $lastPrice->getPeriodDate()->getTimestamp()) / 60) : 0;
        if ($periodMinutes > 0) {
            $lastPrice->setPeriodMinutes(max($lastPrice->getPeriodMinutes(), (int) round($periodMinutes * self::PERIOD_MINUTES_MULTIPLIER)));
        }
        $lastPrice->setPeriodDate($currentDateTime);
    }
}
