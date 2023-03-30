<?php

declare(strict_types=1);

namespace App\Service\Binance;

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
}
