<?php

declare(strict_types=1);

namespace App\Service;

class TelegramMessageSender
{
    private string $token = '6057642569:AAGBHg09GL_R4UYfWSJRv_a-yzC94lQJpF4';
    private int $chatId = -983228694;

    public function __construct(
        private string $environmentId,
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
