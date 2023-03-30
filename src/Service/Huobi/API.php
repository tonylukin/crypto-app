<?php

declare(strict_types=1);

namespace App\Service\Huobi;

use App\Service\ApiInterface;
use App\Service\ExchangeCredentialsInterface;
use Lin\Huobi\HuobiSpot;
use Nzo\UrlEncryptorBundle\Encryptor\Encryptor;

/**
 * @link https://huobiapi.github.io/docs/spot/v1/en/#trading
 */
class API extends HuobiSpot implements ApiInterface
{
    private const FEE_PERCENT_FOR_CALCULATING = 0.2;
    private const FEE_PERCENT = 0; // basically, it is 0.2 for huobi, but we use HT for that which gives a discount

    public function __construct(
        private string $environmentId,
        private Encryptor $encryptor,
    ) {
        parent::__construct();
    }

    public function price(string $symbol): float
    {
        $symbol = strtolower($symbol);
        $result = $this->market()->getTrade([
            'symbol' => $symbol,
        ]);

        return $result['tick']['data'][0]['price'] ?? throw new \Exception('Can not get price');
    }

    /**
     * @return array{status: string, data: int}
     */
    public function buyLimit(string $symbol, float $quantity, float $price): array
    {
        if ($this->environmentId !== 'prod') {
            return [];
        }

        $result = $this->order()->postPlace([
            'account-id' => $this->getAccountId(),
            'symbol' => strtolower($symbol),
            'type' => 'buy-limit',
            'amount' => $quantity,
            'price' => $price,
        ]);

        return $result;
    }

    public function sellLimit(string $symbol, float $quantity, float $price): array
    {
        if ($this->environmentId !== 'prod') {
            return [];
        }

        $result = $this->order()->postPlace([
            'account-id' => $this->getAccountId(),
            'symbol' => strtolower($symbol),
            'type' => 'sell-limit',
            'amount' => $quantity,
            'price' => $price,
        ]);

        return $result;
    }

    public function getFeeMultiplier(bool $usedForCalculatingOnly = false): float
    {
        return ($usedForCalculatingOnly ? self::FEE_PERCENT_FOR_CALCULATING : self::FEE_PERCENT) / 100;
    }

    public function setCredentials(ExchangeCredentialsInterface $user): ApiInterface
    {
        if ($user->getHuobiApiKey() && $user->getHuobiApiSecret()) {
            $this->key = $user->getHuobiApiKey();
            $this->secret = $this->encryptor->decrypt($user->getHuobiApiSecret());
        }

        return $this;
    }

    private function getAccountId(): int
    {
        $result = $this->account()->get();

        return $result['data'][0]['id'] ?? throw new \Exception('Can not get account id');
    }
}
