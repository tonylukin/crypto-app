<?php

declare(strict_types=1);

namespace App\Service\Binance;

use App\Service\ApiInterface;
use App\Service\ExchangeCredentialsInterface;
use Nzo\UrlEncryptorBundle\Encryptor\Encryptor;

class API extends \Binance\API implements ApiInterface
{
    private const FEE_PERCENT = 0.5;

    public function __construct(
        string $apiKey,
        string $apiSecret,
        private string $environmentId,
        private Encryptor $encryptor,
    ) {
        parent::__construct($apiKey, $apiSecret);
        $this->caOverride = true;
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

    public function getFeeMultiplier(): float
    {
        return self::FEE_PERCENT / 100;
    }

    public function setCredentials(ExchangeCredentialsInterface $user): self
    {
        if ($user->getBinanceApiKey() && $user->getBinanceApiSecret()) {
            $this->api_key = $user->getBinanceApiKey();
            $this->api_secret = $this->encryptor->decrypt($user->getBinanceApiSecret());
        }

        return $this;
    }
}
