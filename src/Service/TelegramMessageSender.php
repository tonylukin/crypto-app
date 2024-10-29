<?php

declare(strict_types=1);

namespace App\Service;

class TelegramMessageSender
{
    public function __construct(
        private readonly string $environmentId,
        private string          $token,
        private int             $chatId,
    ) {}

    public function setCredentials(TelegramCredentialsInterface $user): self
    {
        if ($user->getToken()) {
            $this->token = $user->getToken();
        }
        if ($user->getChatId()) {
            $this->chatId = $user->getChatId();
        }

        return $this;
    }

    public function send(string $text): array
    {
        if ($this->environmentId !== 'prod') {
            return [];
        }

        $textMessage = urlencode($text);
        $urlQuery = "https://api.telegram.org/bot{$this->token}/sendMessage?chat_id={$this->chatId}&text={$textMessage}";

        $result = file_get_contents($urlQuery);
        return json_decode($result, true);
    }
}
