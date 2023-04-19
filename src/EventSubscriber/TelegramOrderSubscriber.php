<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Order;
use App\Event\BuyOrderCreatedEvent;
use App\Event\PartialFilledOrderFoundEvent;
use App\Event\SellOrderCreatedEvent;
use App\Event\UnfilledOrderRejectedEvent;
use App\Service\TelegramMessageSender;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TelegramOrderSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private TelegramMessageSender $telegramMessageSender,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            BuyOrderCreatedEvent::class => 'onBuyOrderCreated',
            SellOrderCreatedEvent::class => 'onSellOrderCreated',
            ConsoleEvents::ERROR => 'onConsoleError',
            UnfilledOrderRejectedEvent::class => 'onUnfilledOrderRejected',
            PartialFilledOrderFoundEvent::class => 'onPartialFilledOrderFound',
        ];
    }

    public function onBuyOrderCreated(BuyOrderCreatedEvent $event): void
    {
        $order = $event->getOrder();
        $message = "Buy order created for {$order->getSymbol()->getName()} with price {$order->getPrice()} [{$order->getExchangeLabel()}]";
        $this->telegramMessageSender->setCredentials($order->getUser())->send($message);
    }

    public function onSellOrderCreated(SellOrderCreatedEvent $event): void
    {
        $order = $event->getOrder();
        $message = "Sell order created for {$order->getSymbol()->getName()} with profit {$order->getProfit()} [{$order->getExchangeLabel()}]";
        $this->telegramMessageSender->setCredentials($order->getUser())->send($message);
    }

    public function onConsoleError(ConsoleErrorEvent $event): void
    {
        $this->telegramMessageSender->send($event->getError()->getMessage());
    }

    public function onUnfilledOrderRejected(UnfilledOrderRejectedEvent $event): void
    {
        $order = $event->getOrder();
        if ($order->getStatus() === Order::STATUS_BUY) {
            $message = "Order for {$order->getSymbol()->getName()} removed [{$order->getExchangeLabel()}]";
        } else {
            $message = "Order for {$order->getSymbol()->getName()} with profit {$order->getProfit()} unsold [{$order->getExchangeLabel()}]";
        }
        $this->telegramMessageSender->setCredentials($order->getUser())->send($message);
    }

    public function onPartialFilledOrderFound(PartialFilledOrderFoundEvent $event): void
    {
        $order = $event->getOrder();
        $message = "Partially filled order found for {$order->getSymbol()->getName()} [{$order->getExchangeLabel()}]";
        $this->telegramMessageSender->setCredentials($order->getUser())->send($message);
    }
}
