<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\DataFixtures\PriceFixture;
use App\Entity\Price;
use App\Entity\User;
use App\Entity\UserSymbol;
use App\Repository\SymbolRepository;
use App\Service\ApiInterface;
use App\Service\BestPriceAnalyzer;
use App\Service\Binance\API as API;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BestPriceAnalyzerTest extends KernelTestCase
{
    private ?BestPriceAnalyzer $bestPriceAnalyzer;
    private array $symbols;
    private User $user;
    private \App\Repository\PriceRepository $priceRepository;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        $container = static::getContainer();
        $apiMock = $this->createMock(ApiInterface::class);
        $container->set(API::class, $apiMock);
        $this->bestPriceAnalyzer = $container->get(BestPriceAnalyzer::class);
        $symbolRepository = $container->get(SymbolRepository::class);
        $this->symbols = $symbolRepository->getActiveList();
        $this->user = (new User())
            ->setUsername(uniqid('user'))
            ->setPassword('1')
        ;
        $this->user->getUserSetting()->setMinPricesCountMustHaveBeforeOrder(6);
        $this->em = $container->get(EntityManagerInterface::class);
        $this->em->persist($this->user);
        $this->priceRepository = $this->em->getRepository(Price::class);
        $this->em->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->em->getConnection()->rollBack();
        parent::tearDown();
    }

    /**
     * @dataProvider priceForOrderWithMovingProvider
     */
    public function testGetBestPriceForOrderWithMoving(
        string $startDateFormat,
        ?string $endDateFormat,
        string $symbol,
        ?float $bestPriceForOrder,
        bool $strict = false,
    ): void {
        ini_set('memory_limit', '2G');
        $startDate = new \DateTimeImmutable($startDateFormat);
        $endDate = $endDateFormat !== null ? new \DateTimeImmutable($endDateFormat) : $startDate->modify('+2 weeks');
        $interval = '+10 minutes';
        $userSymbol = (new UserSymbol())
            ->setSymbol($this->symbols[$symbol])
            ->setUser($this->user)
        ;
        while (true) {
            $price = $this->priceRepository->findOneBy([
                'datetime' => $startDate,
                'symbol' => $userSymbol->getSymbol(),
            ]);
            if ($price !== null) {
                $this->bestPriceAnalyzer->setCurrentDateTime($startDate);
                $result = $this->bestPriceAnalyzer->getBestPriceForOrder($userSymbol, $price->getPrice() * 1.000);

                if ($strict) {
                    if ($startDate >= $endDate) {
                        if ($result !== null) {
                            self::assertSame($bestPriceForOrder, $result[0]);
                        } else {
                            self::assertNull($bestPriceForOrder);
                        }
                        return;
                    } elseif ($result !== null) {
                        self::assertNull($result[0], 'Test failed because value comes before end date');
                        return;
                    }
                } else {
                    if ($endDateFormat !== null) {
                        if ($startDate >= $endDate) {
                            if ($result !== null) {
                                self::assertSame($bestPriceForOrder, $result[0]);
                            } else {
                                self::assertNull($bestPriceForOrder);
                            }
                            return;
                        }
                    } else {
                        if ($result !== null) {
                            self::assertSame($bestPriceForOrder, $result[0]);
                            return;
                        }
                        if ($startDate >= $endDate) {
                            self::assertNull($bestPriceForOrder);
                            return;
                        }
                    }
                }
            }

            if ($startDate >= $endDate) {
                self::assertNull($bestPriceForOrder);
                return;
            }

            $startDate = $startDate->modify($interval);
        }
    }

    public function priceForOrderWithMovingProvider(): \Generator
    {
        yield ['2023-03-26 00:08', null, PriceFixture::BTC_REAL_MOVING_SYMBOL, null, true];
        yield ['2023-04-14 00:08', '2023-04-26 22:08', PriceFixture::UMA_REAL_MOVING_SYMBOL, null, true];
        yield ['2023-04-19 23:28', null, PriceFixture::UMA_REAL_MOVING_SYMBOL, null, true];
        yield ['2023-04-16 23:28', '2023-04-23 17:38', PriceFixture::SOL_REAL_MOVING_SYMBOL, 21.51, true];
        yield ['2023-04-16 00:28', '2023-04-30 01:58', PriceFixture::MATIC_REAL_MOVING_SYMBOL, 0.991, true];
        yield ['2023-04-29 11:58', '2023-05-28 22:28', PriceFixture::BNB_REAL_MOVING_SYMBOL, null, true];
        yield ['2023-04-13 23:58', '2023-05-20 23:58', PriceFixture::DOT_REAL_MOVING_SYMBOL, null, true];
        yield ['2023-04-01 00:08', '2023-05-04 23:58', PriceFixture::ELF_REAL_MOVING_SYMBOL, null, true];
        yield ['2023-03-01 00:08', '2023-03-25 20:08', PriceFixture::ILV_REAL_MOVING_SYMBOL, 59.0, true];
        yield ['2023-04-13 00:08', '2023-04-22 22:58', PriceFixture::AGLD_REAL_MOVING_SYMBOL, null, true];
        yield ['2023-04-14 00:08', '2023-05-01 13:08', PriceFixture::AGLD_REAL_MOVING_SYMBOL, null, true];
        yield ['2023-05-01 22:58', '2023-06-21 22:58', PriceFixture::BTC_REAL_MOVING_SYMBOL_2, null, true];
        yield ['2023-05-21 22:58', '2023-06-19 22:58', PriceFixture::ETH_USDT_SYMBOL, null, true];
    }
}
