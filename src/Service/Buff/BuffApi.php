<?php

declare(strict_types=1);

namespace App\Service\Buff;

use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\RequestOptions;

class BuffApi
{
    private const CODE_OK = 'OK';
    private const CODE_LOGIN_REQUIRED = 'Login Required';

    private const GAME_CSGO = 'csgo';
    private const GAME_DOTA = 'dota2';
    private const GAME_RUST = 'rust';
    private const GAME_TF2 = 'tf2';
    private const GAME_CODE_MAPPING = [
        GameId::CSGO => self::GAME_CSGO,
        GameId::DOTA2 => self::GAME_DOTA,
        GameId::RUST => self::GAME_RUST,
        GameId::TF2 => self::GAME_TF2,
    ];

    private const SALE_URL = 'https://buff.163.com/api/market/goods?use_suggestion=0&trigger=undefined_trigger';
    private const BUY_URL = 'https://buff.163.com/api/market/goods/buying';

    private ?string $lastError = null;
    private string $session = '';
    private \GuzzleHttp\Client $client;

    public function __construct()
    {
        $this->client = new \GuzzleHttp\Client();
    }

    public function setAuthSession(string $session): self
    {
        $this->session = $session;

        return $this;
    }

    public function getSaleItems(int $appId = GameId::CSGO, int $page = 1): ?array
    {
        return $this->getItems(self::SALE_URL, $appId, $page);
    }

    public function getBuyItems(int $appId = GameId::CSGO, int $page = 1): ?array
    {
        return $this->getItems(self::BUY_URL, $appId, $page);
    }

    private function getItems(string $url, int $appId, int $page): ?array
    {
        try {
            $res = $this->client->get($url, [
                RequestOptions::QUERY => [
                    'game' => self::GAME_CODE_MAPPING[$appId] ?? self::GAME_CSGO,
                    'page_num' => $page,
                    '_' => time() * 1000,
                ],
                RequestOptions::COOKIES => $this->getCookies(),
            ]);
            $json = json_decode($res->getBody()->getContents(), true);
            $code = $json['code'] ?? [];
            if ($code === self::CODE_LOGIN_REQUIRED) {
                throw new \Exception('You need to get auth cookie');
            }

            $data = $json['data'] ?? [];
            $totalPages = $data['total_page'] ?? 0;
            if ($page > $totalPages) {
                throw new \Exception('Total page size exceeded');
            }

            $items = $data['items'] ?? [];

        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            return null;
        }

        return $items;
    }

    private function getCookies(): CookieJar
    {
        return CookieJar::fromArray([
            'session' => $this->session,
        ], 'buff.163.com');
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }
}
