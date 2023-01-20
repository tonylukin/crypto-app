<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Order;
use App\Entity\User;
use App\Entity\UserSymbol;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class OrderManager
{
    private const MINIMAL_PRICE_DIFF_PERCENT_AFTER_LAST_SELL = 5;
    public const MINIMAL_PROFIT_PERCENT = 1;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private OrderRepository $orderRepository,
        private BestPriceAnalyzer $bestPriceAnalyzer,
        private ApiInterface $api,
        private LoggerInterface $logger
    )
    {}

    public function buy(User $user, UserSymbol $userSymbol, float $totalPrice): bool
    {
        $pendingOrder = $this
            ->orderRepository
            ->findPendingOrder($user, $userSymbol->getSymbol())
        ;
        if ($pendingOrder !== null) {
            return false;
        }

        $price = $this->bestPriceAnalyzer->getBestPriceForOrder($userSymbol);
        if ($price === null) {
            return false;
        }

        // если только что была продажа, смотрим изменение цены - она должна измениться мин. на 5% и упасть
        $order = $this->orderRepository->getLastFinishedOrder($user, $userSymbol->getSymbol());
        if ($order !== null && $order->getSellDate()->modify('+24 hours') > new \DateTime()
            && ($order->getSellPrice() - $price) / $price * 100 < self::MINIMAL_PRICE_DIFF_PERCENT_AFTER_LAST_SELL) {
//            $this->logger->warning("Not enough time and price difference from the last order for symbol {$symbol->getName()}");
            return false;
        }

        if ($price < 5) { // DOGE etc
            $quantity = floor($totalPrice / $price);
        } elseif ($price < 20) { // MATIC etc
            $quantity = round($totalPrice / $price, 1);
        } else {
            $quantity = round($totalPrice / $price, $price > 1000 ? 4 : 2);
        }
        $this->entityManager->beginTransaction();
        try {
            $order = (new Order())
                ->setSymbol($userSymbol->getSymbol())
                ->setPrice($price)
                ->setQuantity($quantity)
                ->setBuyReason($this->bestPriceAnalyzer->getReason())
            ;
            $this->entityManager->persist($order);
            $this->entityManager->flush();
            $response = $this->api->buyLimit($userSymbol->getSymbol()->getName(), $quantity, $price);
            $this->logger->warning('Buy response', ['response' => $response]);
            $this->entityManager->commit();

        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            $this->logger->error($e->getMessage(), [
                'symbol' => $userSymbol->getSymbol()->getName(),
                'totalPrice' => $quantity * $price,
                'method' => __METHOD__
            ]);
            return false;
        }

        $this->logger->info("Buy order created for {$price} {$userSymbol->getSymbol()->getName()}");
        return true;
    }

    public function sell(User $user, UserSymbol $userSymbol): bool
    {
        $pendingOrder = $this
            ->orderRepository
            ->findPendingOrder($user, $userSymbol->getSymbol())
        ;
        if ($pendingOrder === null) {
            return false;
        }

        $price = $this->bestPriceAnalyzer->getBestPriceForSell($userSymbol);
        if ($price === null) {
            return false;
        }

        $profit = $this->bestPriceAnalyzer->getPriceProfit($pendingOrder, $price);
        if ($profit === null) {
            $this->logger->info("Profit is too low, price: {$price} {$userSymbol->getSymbol()->getName()}", ['method' => __METHOD__]);
            return false;
        }

        $this->entityManager->beginTransaction();
        try {
            $pendingOrder
                ->setStatus(Order::STATUS_SELL)
                ->setProfit($profit)
                ->setSellPrice($price)
                ->setSellDate(new \DateTimeImmutable())
                ->setSellReason($this->bestPriceAnalyzer->getReason())
            ;
            $this->entityManager->flush();
            $response = $this->api->sellLimit($userSymbol->getSymbol()->getName(), $pendingOrder->getQuantity(), $price);
            $this->logger->warning('Sell response', ['response' => $response]);
            $this->entityManager->commit();

        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            $this->logger->error($e->getMessage(), [
                'symbol' => $userSymbol->getSymbol()->getName(),
                'totalPrice' => $pendingOrder->getQuantity() * $price,
                'method' => __METHOD__
            ]);
            return false;
        }

        $this->logger->info("Sell order created for {$price} {$userSymbol->getSymbol()->getName()}");
        return true;
    }
}
