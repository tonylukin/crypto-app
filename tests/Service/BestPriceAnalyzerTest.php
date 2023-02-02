<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\DataFixtures\PriceFixture;
use App\Entity\User;
use App\Entity\UserSymbol;
use App\Repository\SymbolRepository;
use App\Service\ApiInterface;
use App\Service\BestPriceAnalyzer;
use App\Service\Binance\API as API;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BestPriceAnalyzerTest extends KernelTestCase
{
    private ?BestPriceAnalyzer $bestPriceAnalyzer;
    private ApiInterface $apiMock;
    private array $symbols;
    private User $user;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->apiMock = $this->createMock(ApiInterface::class);
        $container->set(API::class, $this->apiMock);
        $this->bestPriceAnalyzer = $container->get(BestPriceAnalyzer::class);
        $symbolRepository = $container->get(SymbolRepository::class);
        $this->symbols = $symbolRepository->getActiveList();
        $this->user = new User();
    }

    /**
     * @dataProvider priceForOrderProvider
     */
    public function testGetBestPriceForOrder(float $price, string $symbol, bool $resultIsNotNull): void
    {
        $this->apiMock
            ->expects($this->any())
            ->method('price')
            ->willReturn($price)
        ;
        $userSymbol = (new UserSymbol())
            ->setSymbol($this->symbols[$symbol])
            ->setUser($this->user)
        ;
        $result = $this->bestPriceAnalyzer->getBestPriceForOrder($userSymbol);
        if ($resultIsNotNull) {
            self::assertSame($price, $result);
        } else {
            self::assertNull($result);
        }
    }

    public function priceForOrderProvider(): \Generator
    {
        // avg price = 37.29, last price = 37.13
        yield [31.95, PriceFixture::PRICE_TO_TOP_SYMBOL, false]; // price is not match because is falling down
        yield [36.8, PriceFixture::PRICE_TO_TOP_SYMBOL, false]; // price is not match because is a plato but after rising
        yield [43.0, PriceFixture::PRICE_TO_TOP_SYMBOL, false]; // price is not match because is rising up after falling
        yield [1596.09, PriceFixture::NOT_RECENTLY_CHANGED_PRICE_SYMBOL, false]; // price is not match because is not changed direction
        yield [1552.09, PriceFixture::RECENTLY_CHANGED_PRICE_SYMBOL, true]; // price is match because is changed direction
        yield [1599.09, PriceFixture::RECENTLY_CHANGED_PRICE_SYMBOL, false]; // price is not match because is changed direction but step it too big
    }

    /**
     * @dataProvider priceForSaleProvider
     */
    public function testGetBestPriceForSale(float $price, string $symbol, bool $resultIsNotNull): void
    {
        $this->apiMock
            ->expects($this->any())
            ->method('price')
            ->willReturn($price)
        ;
        $userSymbol = (new UserSymbol())
            ->setSymbol($this->symbols[$symbol])
            ->setUser($this->user)
        ;
        $result = $this->bestPriceAnalyzer->getBestPriceForSell($userSymbol);
        if ($resultIsNotNull) {
            self::assertSame($price, $result);
        } else {
            self::assertNull($result);
        }
    }

    public function priceForSaleProvider(): \Generator
    {
        // avg price = 37.29, last price = 37.13
        yield [21.95, PriceFixture::PRICE_TO_TOP_SYMBOL, true]; // Плохой тест, идет цена и вдруг падает вниз, это не разворот. price is match because have recently changed direction
        yield [36.8, PriceFixture::PRICE_TO_TOP_SYMBOL, true]; // price is match because is a plato
        yield [43.0, PriceFixture::PRICE_TO_TOP_SYMBOL, true]; // price is match because we have changing direction
        // avg price = , last price = 28.49
        yield [35, PriceFixture::PRICE_TO_BOTTOM_SYMBOL, false]; // price is not match because is still rising up
        yield [29.5, PriceFixture::PRICE_TO_BOTTOM_SYMBOL, false]; // price is not match because is rising up
        // avg price = , last price = 1082.77
        yield [1080, PriceFixture::PRICE_TOP_BOTTOM_TOP_SYMBOL, true];
        yield [1090, PriceFixture::PRICE_TOP_BOTTOM_TOP_SYMBOL, true];
        yield [0.370, PriceFixture::RECENTLY_CHANGED_PRICE_WITH_PLATO_SYMBOL, true]; // price is match because is changed direction with a plato (real case)
    }
}
