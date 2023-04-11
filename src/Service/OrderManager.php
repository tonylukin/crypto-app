<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Order;
use App\Entity\Symbol;
use App\Entity\User;
use App\Entity\UserSymbol;
use App\Lib\Math;
use App\Repository\OrderRepository;
use App\Repository\SymbolRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class OrderManager
{
    private ApiInterface $api;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private OrderRepository $orderRepository,
        private BestPriceAnalyzer $bestPriceAnalyzer,
        private LoggerInterface $logger,
        private SymbolRepository $symbolRepository,
    )
    {}

    public function setApi(ApiInterface $api, ExchangeCredentialsInterface $user): self
    {
        $this->api = $api;
        $this->api->setCredentials($user);

        return $this;
    }

    public function buy(UserSymbol $userSymbol, float $totalPrice): bool
    {
        $user = $userSymbol->getUser();
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

        // если только что была продажа, смотрим изменение цены - она должна измениться мин. на {n}% и упасть
        $order = $this->orderRepository->getLastOrder($user, $userSymbol->getSymbol(), Order::STATUS_SELL);
        if ($order !== null && $order->getSellDate()->modify('+24 hours') > new \DateTime()
            && ($order->getSellPrice() - $price) / $price * 100 < $user->getUserSetting()->getMinPriceDiffPercentAfterLastSell()) {
            return false;
        }

        $precision = Math::getPrecisionByAmount($price);
        $quantityBeforeFee = round(($totalPrice / $price) / (1 - $this->api->getFeeMultiplier()), $precision);
        $quantityAfterFee = Math::roundDown($quantityBeforeFee * (1 - $this->api->getFeeMultiplier()), $precision);

        $this->entityManager->beginTransaction();
        try {
            $order = (new Order())
                ->setSymbol($userSymbol->getSymbol())
                ->setPrice($price)
                ->setQuantity($quantityAfterFee)
                ->setBuyReason($this->bestPriceAnalyzer->getReason())
                ->setUser($user)
            ;
            $this->entityManager->persist($order);
            $this->entityManager->flush();

            $response = $this->api->buyLimit($userSymbol->getSymbol()->getName(), $quantityBeforeFee, $price);
            $this->logger->warning('Buy response', [
                'user' => $user->getUserIdentifier(),
                'symbol' => $userSymbol->getSymbol()->getName(),
                'response' => $response,
                'quantity' => $quantityAfterFee,
                'price' => $price
            ]);
            $this->entityManager->commit();

        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            $this->logger->error($e->getMessage(), [
                'user' => $user->getUserIdentifier(),
                'symbol' => $userSymbol->getSymbol()->getName(),
                'quantityBeforeFee' => $quantityBeforeFee,
                'quantityAfterFee' => $quantityAfterFee,
                'price' => $price,
                'totalPrice' => $quantityBeforeFee * $price,
                'method' => __METHOD__
            ]);
            return false;
        }

        $this->logger->info("Buy order created for {$price} {$userSymbol->getSymbol()->getName()}");
        return true;
    }

    public function sell(UserSymbol $userSymbol): bool
    {
        $user = $userSymbol->getUser();
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
            $this->logger->warning('Sell response', [
                'user' => $user->getUserIdentifier(),
                'symbol' => $userSymbol->getSymbol()->getName(),
                'response' => $response,
                'quantity' => $pendingOrder->getQuantity(),
                'price' => $price
            ]);
            $this->entityManager->commit();

        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            $this->logger->error($e->getMessage(), [
                'user' => $user->getUserIdentifier(),
                'symbol' => $userSymbol->getSymbol()->getName(),
                'quantity' => $pendingOrder->getQuantity(),
                'price' => $price,
                'totalPrice' => $pendingOrder->getQuantity() * $price,
                'method' => __METHOD__
            ]);
            return false;
        }

        $this->logger->info("Sell order created for {$price} {$userSymbol->getSymbol()->getName()}");
        return true;
    }

    public function unsold(Order $order): void
    {
        $order
            ->setStatus(Order::STATUS_BUY)
            ->setProfit(null)
            ->setSellPrice(null)
            ->setSellDate(null)
            ->setSellReason(null)
        ;
        $this->entityManager->flush();
    }

    /**
     * @return array<array{orderId: int, quantity: float, symbol: string, status: string}>
     */
    public function cancelUnfilledOrders(User $user): array
    {
        $result = $this->api->cancelUnfilledOrders();
        if (empty($result)) {
            return [];
        }

        $symbolNames = array_keys($result);
        /** @var Symbol[] $symbols */
        $symbols = [];
        foreach ($this->symbolRepository->findByName($symbolNames) as $symbol) {
            $symbols[$symbol->getName()] = $symbol;
        }

        $output = [];
        foreach ($symbolNames as $symbolName) {
            $order = $this->orderRepository->getLastOrder($user, $symbols[$symbolName]);
            // todo let's look if there will be problems with it
            if ($order === null || $order->getStatus() !== $result[$symbolName]['type']) { //  && $order->getQuantity() !== $result[$symbolName]['quantity']
                throw new \Exception("Last order has different status for '{$symbolName}' of order #{$order->getId()}");
            }

            $output[] = [
                'orderId' => $order->getId(),
                'quantity' => $order->getQuantity(),
                'symbol' => $symbolName,
                'status' => $order->getStatus(),
            ];
            $this->entityManager->remove($order);
        }

        $this->entityManager->flush();
        return $output;
    }
}
