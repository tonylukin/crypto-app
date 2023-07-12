<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\DataFixtures\PriceFixture;
use App\Entity\LastPrice;
use App\Entity\Order;
use App\Entity\User;
use App\Entity\UserSymbol;
use App\Repository\OrderRepository;
use App\Repository\SymbolRepository;
use App\Repository\UserRepository;
use App\Service\ApiInterface;
use App\Service\BestPriceAnalyzer;
use App\Service\OrderManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class OrderManagerTest extends KernelTestCase
{
    private BestPriceAnalyzer|null $bestPriceAnalyzer;
    private null|OrderManager $orderManager;
    /**
     * @var \App\Entity\Symbol[]
     */
    private array $symbols;
    /**
     * @var User[]
     */
    private array $users;
    private null|\Doctrine\ORM\EntityManager $em;
    private OrderRepository $orderRepositoryMock;
    private ApiInterface $apiMock;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        $container = static::getContainer();
        $this->orderRepositoryMock = $this->createMock(OrderRepository::class);
        $container->set(OrderRepository::class, $this->orderRepositoryMock);
        $this->bestPriceAnalyzer = $this->createMock(BestPriceAnalyzer::class);
        $container->set(BestPriceAnalyzer::class, $this->bestPriceAnalyzer);
        $this->orderManager = $container->get(OrderManager::class);
        $userRepository = $container->get(UserRepository::class);
        $this->users = $userRepository->findAll();
        $symbolRepository = $container->get(SymbolRepository::class);
        $this->symbols = $symbolRepository->getActiveList();
        $this->apiMock = $this->createMock(ApiInterface::class);
        $this->apiMock
            ->method('price')
            ->willReturn(22.22)
        ;
        $this->em = $container->get(EntityManagerInterface::class);
        $this->em->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->em->getConnection()->rollBack();
        parent::tearDown();
    }

    /**
     * @dataProvider buyProvider
     */
    public function testBuy(float $lowerThreshold, bool $expectedResult): void
    {
        $this->bestPriceAnalyzer
            ->expects($this->any())
            ->method('getBestPriceForOrder')
            ->willReturn([22.22, new LastPrice()])
        ;
        foreach ($this->users as $user) {
            $userSymbol = (new UserSymbol())
                ->setSymbol($this->symbols[PriceFixture::BTC_REAL_MOVING_SYMBOL])
                ->setUser($user)
                ->setLowerThreshold($lowerThreshold)
            ;
            $this->orderManager->setApi(
                $this->apiMock,
                $user
            );
            $result = $this->orderManager->buy($userSymbol, 5);
            self::assertSame($expectedResult, $result);
        }
    }

    public function buyProvider(): \Generator
    {
        yield [22.23, true];
        yield [22.21, false];
    }

    /**
     * @dataProvider sellProvider
     */
    public function testSell(float $upperThreshold, bool $expectedResult): void
    {
        $this->orderRepositoryMock
            ->expects($this->any())
            ->method('findPendingOrder')
            ->willReturn((new Order())
                ->setQuantity(374.8049)
                ->setPrice(2.662)
                ->setSymbol($this->symbols[PriceFixture::BTC_REAL_MOVING_SYMBOL])
                ->setUser(current($this->users))
            )
        ;
        $this->bestPriceAnalyzer
            ->expects($this->any())
            ->method('getBestPriceForSell')
            ->willReturn(2.962)
        ;
        $this->bestPriceAnalyzer
            ->expects($this->any())
            ->method('getPriceProfit')
            ->willReturn(80.1)
        ;
        foreach ($this->users as $user) {
            $userSymbol = (new UserSymbol())
                ->setSymbol($this->symbols[PriceFixture::BTC_REAL_MOVING_SYMBOL])
                ->setUser($user)
                ->setUpperThreshold($upperThreshold)
            ;
            $this->orderManager->setApi(
                $this->apiMock,
                $user
            );
            $result = $this->orderManager->sell($userSymbol);
            self::assertSame($expectedResult, $result);
        }
    }

    public function sellProvider(): \Generator
    {
        yield [2.9619999, true];
        yield [2.962, false];
    }

    public function testCancelUnfilledOrders(): void
    {
        $user = current($this->users);
        $this->orderManager->setApi($this->apiMock, $user);
        $this->apiMock
            ->method('cancelUnfilledOrders')
            ->willReturn([
                PriceFixture::BTC_REAL_MOVING_SYMBOL => [
                    'type' => Order::STATUS_BUY,
                    'partialQuantity' => 0.4025,
                ],
            ])
        ;
        $order = (new Order())
            ->setSymbol($this->symbols[PriceFixture::BTC_REAL_MOVING_SYMBOL])
            ->setPrice(11.11)
            ->setQuantity(1)
            ->setUser($user)
        ;
        $this->orderRepositoryMock
            ->expects($this->once())
            ->method('getLastOrder')
            ->willReturn($order)
        ;
        $result = $this->orderManager->cancelUnfilledOrders($user);
        $this->assertEmpty($result);
        $this->assertTrue($order->isPartial());
    }
}
