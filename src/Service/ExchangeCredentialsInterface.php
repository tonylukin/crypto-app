<?php

namespace App\Service;

interface ExchangeCredentialsInterface
{
    public function getBinanceApiKey(): ?string;

    public function getBinanceApiSecret(): ?string;
}
