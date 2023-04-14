<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\BuyOrderCreatedEvent;
use App\Event\SellOrderCreatedEvent;
use App\Service\TelegramMessageSender;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderSubscriber implements EventSubscriberInterface
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
}
