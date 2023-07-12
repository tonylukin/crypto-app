<?php

namespace App\DataFixtures;

use App\Entity\Price;
use App\Entity\Symbol;
use App\Entity\User;
use App\Entity\UserSymbol;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class PriceFixture extends Fixture
{
    public const BTC_REAL_MOVING_SYMBOL = 'BTCUSDT_REAL';
    public const UMA_REAL_MOVING_SYMBOL = 'UMAUSDT_REAL';
    public const SOL_REAL_MOVING_SYMBOL = 'SOLUSDT_REAL';
    public const MATIC_REAL_MOVING_SYMBOL = 'MATICUSDT_REAL';
    public const BNB_REAL_MOVING_SYMBOL = 'BNBUSDT_REAL';
    public const DOT_REAL_MOVING_SYMBOL = 'DOTUSDT_REAL';
    public const ELF_REAL_MOVING_SYMBOL = 'ELFUSDT_REAL';
    public const ILV_REAL_MOVING_SYMBOL = 'ILVUSDT_REAL';
    public const AGLD_REAL_MOVING_SYMBOL = 'AGLDUSDT_REAL';
    public const BTC_REAL_MOVING_SYMBOL_2 = 'BTCUSDT_REAL_2';
    public const ETH_USDT_SYMBOL = 'ETHUSDT';
    private const SYMBOL_WITH_MOVING_LIST = [
        self::BTC_REAL_MOVING_SYMBOL,
        self::UMA_REAL_MOVING_SYMBOL,
        self::SOL_REAL_MOVING_SYMBOL,
        self::MATIC_REAL_MOVING_SYMBOL,
        self::BNB_REAL_MOVING_SYMBOL,
        self::DOT_REAL_MOVING_SYMBOL,
        self::ELF_REAL_MOVING_SYMBOL,
        self::ILV_REAL_MOVING_SYMBOL,
        self::AGLD_REAL_MOVING_SYMBOL,
        self::BTC_REAL_MOVING_SYMBOL_2,
        self::ETH_USDT_SYMBOL,
    ];

    public function load(ObjectManager $manager): void
    {
        ini_set('memory_limit', '2G');
        gc_enable();
        $manager->getConnection()->getConfiguration()->setSQLLogger(null);

        $this->createSymbols($manager);

        foreach ($this->getSymbolData() as $symbolName => $row) {
            $this->savePrices($manager, $symbolName, $row);
        }
    }

    private function savePrices(ObjectManager $manager, string $symbolName, array $row): void
    {
        $i = 0;
        $symbol = $manager->getRepository(Symbol::class)->findOneBy(['name' => $symbolName]);
        foreach ($row as $values) {
            [$dateValue, $priceValue] = $values;
            $price = $this->createPriceEntity($priceValue, $symbol, new \DateTime($dateValue));
            $manager->persist($price);
            $i++;
            if ($i === 100) {
                $manager->flush();
                $manager->clear();
                $i = 0;
                $symbol = $manager->getRepository(Symbol::class)->findOneBy(['name' => $symbolName]);
                gc_collect_cycles();
            }
        }
        $manager->flush();
        $manager->clear();
        gc_collect_cycles();
    }

    private function getSymbolData(): \Generator
    {
        foreach (self::SYMBOL_WITH_MOVING_LIST as $symbolName) {
            yield $symbolName => include __DIR__ . "/Data/{$symbolName}";
        }
    }

    private function createPriceEntity(float $priceValue, Symbol $symbol, \DateTime $currentDate): Price
    {
        $price = new Price();
        $price
            ->setPrice($priceValue)
            ->setSymbol($symbol)
            ->setDatetime($currentDate)
        ;
        return $price;
    }

    private function createSymbols(ObjectManager $manager): array
    {
        $user = $manager->getRepository(User::class)->findOneBy([]);
        /** @var Symbol[] $data */
        $data = [];
        foreach (self::SYMBOL_WITH_MOVING_LIST as $symbolName) {
            $data[$symbolName] = (new Symbol())
                ->setName($symbolName)
            ;
            $userSymbol = (new UserSymbol())
                ->setUser($user)
                ->setSymbol($data[$symbolName])
            ;
            $manager->persist($data[$symbolName]);
            $manager->persist($userSymbol);
        }
        $manager->flush();

        return $data;
    }
}
