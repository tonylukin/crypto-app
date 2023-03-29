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

    public static function outputSmallNumber(float $number): float|string
    {
        if ($number >= 0.0001) {
            return $number;
        }
        if (!preg_match('/(.*?)E-(\d)$/', (string) $number, $matches)) {
            return $number;
        }

        $additionalZeroCount = 0;
        if (str_contains($matches[1], '.')) {
            $additionalZeroCount = strlen(rtrim($matches[1], '0')) - 2; // 2: 1 for int and 1 for dot symbol - 1.098E-5 => 1.098 => 5 - 2 = 3 additional count
        }
        $zeroCount = $additionalZeroCount + (int) $matches[2];
        return sprintf("%.{$zeroCount}f", $number);
    }
}
