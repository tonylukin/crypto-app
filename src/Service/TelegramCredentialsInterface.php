<?php

declare(strict_types=1);

namespace App\Service;

interface TelegramCredentialsInterface
{
    public function getToken(): ?string;

    public function getChatId(): ?int;
}
