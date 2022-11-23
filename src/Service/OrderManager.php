<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Order;
use App\Entity\Symbol;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class OrderManager
{
    public const MINIMAL_PROFIT_PERCENT = 1;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private OrderRepository $orderRepository,
        private BestPriceAnalyzer $bestPriceAnalyzer,
        private ApiInterface $api,
        private LoggerInterface $logger
    )
    {}

    public function buy(Symbol $symbol, float $totalPrice): bool
    {
        $pendingOrder = $this
            ->orderRepository
            ->findPendingOrder($symbol)
        ;
        if ($pendingOrder !== null) {
            return false;
        }

        // если только что была продажа, не нужно опять покупать - нужен интервал в несколько часов, например
        $order = $this->orderRepository->getLastFinishedOrder($symbol);
        if ($order !== null && $order->getSellDate()->modify('+24 hours') > new \DateTime()) {
            $this->logger->warning("Not enough time from the last order for symbol {$symbol->getName()}");
            return false;
        }

        $price = $this->bestPriceAnalyzer->getBestPriceForOrder($symbol);
        if ($price === null) {
            return false;
        }

        if ($price < 1) { // DOGE etc
            $quantity = floor($totalPrice / $price);
        } elseif ($price < 20) { // MATIC etc
            $quantity = round($totalPrice / $price, 1);
        } else {
            $quantity = round($totalPrice / $price, $price > 1000 ? 4 : 2);
        }
        $this->entityManager->beginTransaction();
        try {
            $order = (new Order())
                ->setSymbol($symbol)
                ->setPrice($price)
                ->setQuantity($quantity)
                ->setBuyReason($this->bestPriceAnalyzer->getReason())
            ;
            $this->entityManager->persist($order);
            $this->entityManager->flush();
            $response = $this->api->buyLimit($symbol->getName(), $quantity, $price);
            $this->logger->warning('Buy response', ['response' => $response]);
            $this->entityManager->commit();

        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            $this->logger->error($e->getMessage(), [
                'symbol' => $symbol->getName(),
                'totalPrice' => $quantity * $price,
                'method' => __METHOD__
            ]);
            return false;
        }

        $this->logger->info("Buy order created for {$price} {$symbol->getName()}");
        return true;
    }

    public function sell(Symbol $symbol): bool
    {
        $pendingOrder = $this
            ->orderRepository
            ->findPendingOrder($symbol)
        ;
        if ($pendingOrder === null) {
            return false;
        }

        $price = $this->bestPriceAnalyzer->getBestPriceForSell($symbol);
        if ($price === null) {
            return false;
        }

        $profit = $this->bestPriceAnalyzer->getPriceProfit($pendingOrder, $price);
        if ($profit === null) {
            $this->logger->info("Profit is too low, price: {$price} {$symbol->getName()}", ['method' => __METHOD__]);
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
            $response = $this->api->sellLimit($symbol->getName(), $pendingOrder->getQuantity(), $price);
            $this->logger->warning('Sell response', ['response' => $response]);
            $this->entityManager->commit();

        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            $this->logger->error($e->getMessage(), [
                'symbol' => $symbol->getName(),
                'totalPrice' => $pendingOrder->getQuantity() * $price,
                'method' => __METHOD__
            ]);
            return false;
        }

        $this->logger->info("Sell order created for {$price} {$symbol->getName()}");
        return true;
    }
}
