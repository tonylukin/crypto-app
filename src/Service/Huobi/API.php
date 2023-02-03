<?php

declare(strict_types=1);

namespace App\Service\Huobi;

use App\Service\ApiInterface;
use App\Service\ExchangeCredentialsInterface;
use Lin\Huobi\HuobiSpot;
use Nzo\UrlEncryptorBundle\Encryptor\Encryptor;

// todo see https://huobiapi.github.io/docs/spot/v1/en/#trading
class API extends HuobiSpot implements ApiInterface
{
    private const FEE_PERCENT = 0.5;

    public function __construct(
        private string $environmentId,
        private Encryptor $encryptor,
    ) {
        parent::__construct();
    }

    public function price(string $symbol): float
    {
        // todo implement
    }

    public function buyLimit(string $symbol, float $quantity, float $price): array
    {
        if ($this->environmentId !== 'prod') {
            return [];
        }

        $this->order()->postPlace([
            'account-id' => $this->getAccountId(),
            'symbol' => strtolower($symbol),
            'type' => 'buy-limit',
            'amount' => $quantity,
            'price' => $price,
        ]);
    }

    public function sellLimit(string $symbol, float $quantity, float $price): array
    {
        if ($this->environmentId !== 'prod') {
            return [];
        }

        $this->order()->postPlace([
            'account-id' => $this->getAccountId(),
            'symbol' => strtolower($symbol),
            'type' => 'sell-limit',
            'amount' => $quantity,
            'price' => $price,
        ]);
    }

    public function getFeeMultiplier(): float
    {
        return self::FEE_PERCENT / 100;
    }

    public function setCredentials(ExchangeCredentialsInterface $user): ApiInterface
    {
        if ($user->getHuobiApiKey() && $user->getHuobiApiSecret()) {
            $this->key = $user->getHuobiApiKey();
            $this->secret = $this->encryptor->decrypt($user->getHuobiApiSecret());
        }

        return $this;
    }

    // todo implement it: https://api.huobi.com/v1/account/accounts
    private function getAccountId(): string
    {
        return '';
    }
}
