<?php

declare(strict_types=1);

namespace App\Service\Binance;

use App\Entity\Order;
use App\Service\ApiInterface;
use App\Service\ExchangeCredentialsInterface;
use Nzo\UrlEncryptorBundle\Encryptor\Encryptor;

class API extends \Binance\API implements ApiInterface
{
    private const FEE_PERCENT_FOR_CALCULATING = 0.5;
    private const FEE_PERCENT = 0; // basically, it is 0.5 for binance, but we use BNB for that which gives a discount

    public function __construct(
        private string $environmentId,
        private Encryptor $encryptor,
    ) {
        parent::__construct('', '');
        $this->caOverride = true;
        $this->useServerTime();
    }

    public function getExchange(): int
    {
        return Order::EXCHANGE_BINANCE;
    }

    public function price(string $symbol): float
    {
        return (float) parent::price($symbol);
    }

    public function buyLimit(string $symbol, float $quantity, float $price): array
    {
        if ($this->environmentId !== 'prod') {
            return [];
        }

        return parent::buy($symbol, (string) $quantity, (string) $price);
    }

    public function sellLimit(string $symbol, float $quantity, float $price): array
    {
        if ($this->environmentId !== 'prod') {
            return [];
        }

        return parent::sell($symbol, (string) $quantity, (string) $price);
    }

    public function getFeeMultiplier(bool $usedForCalculatingOnly = false): float
    {
        return ($usedForCalculatingOnly ? self::FEE_PERCENT_FOR_CALCULATING : self::FEE_PERCENT) / 100;
    }

    public function setCredentials(ExchangeCredentialsInterface $user): self
    {
        if ($user->getBinanceApiKey() && $user->getBinanceApiSecret()) {
            $this->api_key = $user->getBinanceApiKey();
            $this->api_secret = $this->encryptor->decrypt($user->getBinanceApiSecret());
        }

        return $this;
    }

    public function cancelUnfilledOrders(): array
    {
        /**
         * Array
         * (
         *     [symbol] => BTCUSDT
         *     [orderId] => 20767857149
         *     [orderListId] => -1
         *     [clientOrderId] => web_c0e8b04c45414b5a89ee8e809e939db6
         *     [price] => 28114.50000000
         *     [origQty] => 0.00462000
         *     [executedQty] => 0.00000000
         *     [cummulativeQuoteQty] => 0.00000000
         *     [status] => NEW
         *     [timeInForce] => GTC
         *     [type] => LIMIT
         *     [side] => BUY
         *     [stopPrice] => 0.00000000
         *     [icebergQty] => 0.00000000
         *     [time] => 1681197852454
         *     [updateTime] => 1681197852454
         *     [isWorking] => 1
         *     [workingTime] => 1681197852454
         *     [origQuoteOrderQty] => 0.00000000
         *     [selfTradePreventionMode] => NONE
         * )
         */
        $result = $this->openOrders();
        if (empty($result)) {
            return [];
        }

        $output = [];
        foreach ($result as $row) {
            if ((float) $row['executedQty'] === 0.0) {
                /**
                 * Array
                 * (
                 *      [symbol] => LUNAUSDT
                 *      [origClientOrderId] => web_bd00bd20861a448ba2938bd55d73711b
                 *      [orderId] => 2397849368
                 *      [orderListId] => -1
                 *      [clientOrderId] => FDCEaeLQCnHQMz2eqKXGVe
                 *      [price] => 6.32590000
                 *      [origQty] => 1.72000000
                 *      [executedQty] => 0.00000000
                 *      [cummulativeQuoteQty] => 0.00000000
                 *      [status] => CANCELED
                 *      [timeInForce] => GTC
                 *      [type] => LIMIT
                 *      [side] => SELL
                 *      [selfTradePreventionMode] => NONE
                 * )
                 */
                $cancelResult = $this->cancel($row['symbol'], $row['orderId']);
                if ($cancelResult['status'] === 'CANCELED') {
                    $output[$row['symbol']] = [
                        'quantity' => $row['origQty'],
                        'type' => $cancelResult['side'] === 'SELL' ? Order::STATUS_SELL : Order::STATUS_BUY,
                    ];
                }
            }
        }

        return $output;
    }
}
