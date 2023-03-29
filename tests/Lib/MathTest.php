<?php

declare(strict_types=1);

namespace App\Tests\Lib;

use App\Lib\Math;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MathTest extends KernelTestCase
{
    /**
     * @dataProvider outputSmallNumbersGenerator
     */
    public function testOutputSmallNumber(float $number, $expectedResult): void
    {
        $result = Math::outputSmallNumber($number);
        $this->assertSame($expectedResult, $result);
    }

    public static function outputSmallNumbersGenerator(): \Generator
    {
        yield [0.00001098, '0.00001098'];
        yield [10, 10.0];
        yield [1, 1.0];
        yield [1.5, 1.5];
        yield [0.5, 0.5];
        yield [0.05, 0.05];
        yield [0.005, 0.005];
        yield [0.0001, 0.0001];
        yield [0.000150001, 0.000150001];
        yield [0.0005, 0.0005];
        yield [0.00005, '0.00005'];
        yield [0.000005, '0.000005'];
        yield [0.0000005, '0.0000005'];
        yield [0.00000005, '0.00000005'];
        yield [0.000000005, '0.000000005'];
    }
}
