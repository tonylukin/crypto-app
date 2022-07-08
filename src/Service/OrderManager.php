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
    public const MINIMAL_PROFIT_PERCENT = 3;

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
        $price = $this->bestPriceAnalyzer->getBestPriceForOrder($symbol);
        if ($price === null) {
            return false;
        }

        $pendingOrder = $this
            ->orderRepository
            ->findPendingOrder($symbol)
        ;
        if ($pendingOrder !== null) {
            return false;
        }

        $quantity = round($totalPrice / $price, 4);
        $this->entityManager->beginTransaction();
        // todo use some multiplier for $price to buy chipper
        try {
            $order = (new Order())
                ->setSymbol($symbol)
                ->setPrice($price)
                ->setQuantity($quantity)
            ;
            $this->entityManager->persist($order);
            $this->entityManager->flush();
            $this->api->buyLimit($symbol->getName(), $quantity, $price);
            $this->entityManager->commit();

        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            $this->logger->error($e->getMessage(), ['method' => __METHOD__]);
            return false;
        }

        $this->logger->info("Buy order created for {$price} {$symbol->getName()}");
        return true;
    }

    public function sell(Symbol $symbol): bool
    {
        $price = $this->bestPriceAnalyzer->getBestPriceForSale($symbol);
        if ($price === null) {
            return false;
        }

        $pendingOrder = $this
            ->orderRepository
            ->findPendingOrder($symbol)
        ;
        if ($pendingOrder === null) {
            return false;
        }
        $profit = $this->bestPriceAnalyzer->getPriceProfit($pendingOrder, $price);
        if ($profit === null) {
            $this->logger->info("Profit is too low, price: {$price} {$symbol->getName()}", ['method' => __METHOD__]);
            return false;
        }

        $this->entityManager->beginTransaction();
        try {
            // todo use some multiplier for $price to sell more expensive
            $pendingOrder
                ->setStatus(Order::STATUS_SALE)
                ->setProfit($profit)
                ->setSalePrice($price)
                ->setSaleDate(new \DateTimeImmutable())
            ;
            $this->entityManager->flush();
            $this->api->sellLimit($symbol->getName(), $pendingOrder->getQuantity(), $price);
            $this->entityManager->commit();

        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            $this->logger->error($e->getMessage(), ['method' => __METHOD__]);
            return false;
        }

        $this->logger->info("Sell order created for {$price} {$symbol->getName()}");
        return true;
    }
}
