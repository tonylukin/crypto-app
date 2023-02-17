<?php

namespace App\Service\Buff;

use Exception;
use UnhandledMatchError;

class GameId
{
    public const TF2 = 440; // https://tf2.tm
    public const RUST = 252490; // https://rust.tm
    public const DOTA2 = 570; // https://market.dota2.net
    public const CSGO = 730; // https://market.csgo.com

    /**
     * @throws Exception
     */
    public static function getIdByName(string $const): ?int
    {
        $fullConst = static::class . '::' . $const;
        return defined($fullConst)
            ? constant($fullConst)
            : throw new Exception('Unknown game.');
    }

    public static function getNameById(int $appId, bool $throw = false): string
    {
        return match ($appId) {
            self::TF2 => 'TF2',
            self::RUST => 'RUST',
            self::DOTA2 => 'DOTA2',
            self::CSGO => 'CSGO',
            default => $throw ? throw new UnhandledMatchError($appId) : 'Undefined',
        };
    }
}
