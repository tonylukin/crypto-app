<?php

declare(strict_types=1);

namespace App\Lib;

class Math
{
    public static function roundDown($decimal, $precision): float
    {
        $sign = $decimal > 0 ? 1 : -1;
        $base = pow(10, $precision);
        return floor(abs($decimal) * $base) / $base * $sign;
    }

    public static function getPrecisionByAmount(float $amount): int
    {
        if ($amount < 5) { // DOGE etc
            return 0;
        } elseif ($amount < 20) { // MATIC etc
            return 1;
        } elseif ($amount < 1000) {
            return 2;
        }

        return 4;
    }
}
