<?php

namespace App\DataFixtures;

use App\Entity\Price;
use App\Entity\Symbol;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class PriceFixture extends Fixture
{
    public const PRICE_TO_TOP_SYMBOL = 'SOLBUSD';
    public const PRICE_TO_BOTTOM_SYMBOL = 'SOL2BUSD';
    public const PRICE_TOP_BOTTOM_TOP_SYMBOL = 'ETHBUSD';
    public const NOT_RECENTLY_CHANGED_PRICE_SYMBOL = 'ETH2BUSD';
    private const SYMBOLS = [
        self::PRICE_TO_TOP_SYMBOL,
        self::PRICE_TO_BOTTOM_SYMBOL,
        self::PRICE_TOP_BOTTOM_TOP_SYMBOL,
        self::NOT_RECENTLY_CHANGED_PRICE_SYMBOL,
    ];

    private const CHANGING_PRICE_15_PERCENT_UP = [
        [28.49,'SOLBUSD'],
        [28.23,'SOLBUSD'],
        [29.05,'SOLBUSD'],
        [29.41,'SOLBUSD'],
        [29.38,'SOLBUSD'],
        [29.36,'SOLBUSD'],
        [29.74,'SOLBUSD'],
        [30.98,'SOLBUSD'],
        [29.9,'SOLBUSD'],
        [29.92,'SOLBUSD'],
        [29.14,'SOLBUSD'],
        [29.07,'SOLBUSD'],
        [29.3,'SOLBUSD'],
        [29.51,'SOLBUSD'],
        [30.23,'SOLBUSD'],
        [30.99,'SOLBUSD'],
        [30.63,'SOLBUSD'],
        [30.28,'SOLBUSD'],
        [28.32,'SOLBUSD'],
        [28.87,'SOLBUSD'],
        [26.95,'SOLBUSD'],
        [26.19,'SOLBUSD'],
        [27.47,'SOLBUSD'],
        [28.55,'SOLBUSD'],
        [27.13,'SOLBUSD'],
        [28.11,'SOLBUSD'],
        [28.59,'SOLBUSD'],
        [28.3,'SOLBUSD'],
        [28.17,'SOLBUSD'],
        [28.06,'SOLBUSD'],
        [28.21,'SOLBUSD'],
        [27.89,'SOLBUSD'],
        [27,'SOLBUSD'],
        [27.03,'SOLBUSD'],
        [26.45,'SOLBUSD'],
        [26.82,'SOLBUSD'],
        [27.37,'SOLBUSD'],
        [27.61,'SOLBUSD'],
        [26.2,'SOLBUSD'],
        [28,'SOLBUSD'],
        [27.81,'SOLBUSD'],
        [28.42,'SOLBUSD'],
        [28.78,'SOLBUSD'],
        [28.41,'SOLBUSD'],
        [29.31,'SOLBUSD'],
        [29.53,'SOLBUSD'],
        [30.57,'SOLBUSD'],
        [31.26,'SOLBUSD'],
        [31.93,'SOLBUSD'],
        [31.94,'SOLBUSD'],
        [32.53,'SOLBUSD'],
        [32.88,'SOLBUSD'],
        [33.34,'SOLBUSD'],
        [33.55,'SOLBUSD'],
        [33.96,'SOLBUSD'],
        [33.47,'SOLBUSD'],
        [31.87,'SOLBUSD'],
        [32.5,'SOLBUSD'],
        [32.72,'SOLBUSD'],
        [32.59,'SOLBUSD'],
        [32.56,'SOLBUSD'],
        [32.99,'SOLBUSD'],
        [33.12,'SOLBUSD'],
        [32.15,'SOLBUSD'],
        [31.71,'SOLBUSD'],
        [31.68,'SOLBUSD'],
        [32.27,'SOLBUSD'],
        [31.66,'SOLBUSD'],
        [32.96,'SOLBUSD'],
        [33.98,'SOLBUSD'],
        [33.91,'SOLBUSD'],
        [34.58,'SOLBUSD'],
        [34.35,'SOLBUSD'],
        [33.77,'SOLBUSD'],
        [34.09,'SOLBUSD'],
        [34.44,'SOLBUSD'],
        [33.87,'SOLBUSD'],
        [33.95,'SOLBUSD'],
        [33.74,'SOLBUSD'],
        [33.91,'SOLBUSD'],
        [35.14,'SOLBUSD'],
        [34.8,'SOLBUSD'],
        [35.44,'SOLBUSD'],
        [36.39,'SOLBUSD'],
        [36.79,'SOLBUSD'],
        [36.49,'SOLBUSD'],
        [37.67,'SOLBUSD'],
        [37.36,'SOLBUSD'],
        [37.2,'SOLBUSD'],
        [37.82,'SOLBUSD'],
        [37.52,'SOLBUSD'],
        [37.6,'SOLBUSD'],
        [37.29,'SOLBUSD'],
        [37.45,'SOLBUSD'],
        [37.13,'SOLBUSD'],
    ];

    private const CHANGING_PRICE_TOP_BOTTOM_TOP = [
        1082.77,
        1085.49,
        1087.03,
        1074.85,
        1081.08,
        1077.76,
        1078.62,
        1076.88,
        1018.5,
        1002.43,
        989.04,
        993.62,
        1004.87,
        1003.99,
        999.14,
        998.52,
        991.59,
        988.12,
        981.44,
        960.95,
        937.23,
        913.73,
        902.89,
        941.74,
        990.6,
        993.29,
        989.88,
        960.07,
        949.67,
        951.65,
        965.57,
        944.42,
        954.56,
        954.26,
        966.15,
        1062.52,
        1054.16,
        1032.01,
        1019.41,
        1033.41,
        1043.41,
        1053.41,
        1063.41,
        1073.41,
        1083.41,
    ];

    private const NOT_RECENTLY_CHANGED_PRICE = [
        1590.19,
        1569.75,
        1561.56,
        1563.94,
        1576.37,
        1589.29,
        1575.2,
        1576.33,
        1589.56,
        1585.29,
        1578.03,
        1543.95,
        1536.93,
    ];

    public function load(ObjectManager $manager): void
    {
        $currentDate = new \DateTime();
        $currentDate->modify('+1 hour')->setTime($currentDate->format('H'), 0);

        $symbols = $this->createSymbols($manager);

        $date = clone $currentDate;
        foreach (array_reverse(self::CHANGING_PRICE_15_PERCENT_UP) as $item) {
            [$priceValue, $symbol] = $item;
            $price = $this->createPriceEntity($priceValue, $symbols[$symbol], $date);
            $manager->persist($price);
        }
        $manager->flush();

        $date = clone $currentDate;
        foreach (self::CHANGING_PRICE_15_PERCENT_UP as $item) {
            [$priceValue] = $item;
            $price = $this->createPriceEntity($priceValue, $symbols[self::PRICE_TO_BOTTOM_SYMBOL], $date);
            $manager->persist($price);
        }
        $manager->flush();

        $date = clone $currentDate;
        foreach (self::CHANGING_PRICE_TOP_BOTTOM_TOP as $priceValue) {
            $price = $this->createPriceEntity($priceValue, $symbols[self::PRICE_TOP_BOTTOM_TOP_SYMBOL], $date);
            $manager->persist($price);
        }
        $manager->flush();

        $date = clone $currentDate;
        foreach (self::NOT_RECENTLY_CHANGED_PRICE as $priceValue) {
            $price = $this->createPriceEntity($priceValue, $symbols[self::NOT_RECENTLY_CHANGED_PRICE_SYMBOL], $date);
            $manager->persist($price);
        }
        $manager->flush();
    }

    private function createPriceEntity(float $priceValue, Symbol $symbol, \DateTimeInterface $currentDate): Price
    {
        $price = new Price();
        $price
            ->setPrice($priceValue)
            ->setSymbol($symbol)
            ->setDatetime(clone $currentDate->modify('-1 hour'))
        ;
        return $price;
    }

    /**
     * @return Symbol[]
     */
    private function createSymbols(ObjectManager $manager): array
    {
        $data = [];
        foreach (self::SYMBOLS as $symbolName) {
            $data[$symbolName] = (new Symbol())
                ->setName($symbolName)
            ;
            $manager->persist($data[$symbolName]);
        }

        return $data;
    }
}
