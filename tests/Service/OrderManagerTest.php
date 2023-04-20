<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\DataFixtures\PriceFixture;
use App\Entity\Order;
use App\Entity\User;
use App\Entity\UserSymbol;
use App\Repository\OrderRepository;
use App\Repository\SymbolRepository;
use App\Repository\UserRepository;
use App\Service\ApiFactory;
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
    private ApiFactory $apiFactory;
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
        $this->apiFactory = $container->get(ApiFactory::class);
        $this->apiMock = $this->createMock(ApiInterface::class);

        $this->em = $container->get(EntityManagerInterface::class);
        $this->em->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->em->getConnection()->rollBack();
        parent::tearDown();
    }

    public function testBuy(): void
    {
        $this->bestPriceAnalyzer
            ->expects($this->any())
            ->method('getBestPriceForOrder')
            ->willReturn(22.22)
        ;
        foreach ($this->users as $user) {
            $userSymbol = (new UserSymbol())
                ->setSymbol($this->symbols[PriceFixture::PRICE_TO_TOP_SYMBOL])
                ->setUser($user)
            ;
            $this->orderManager->setApi(
                $this->apiFactory->build($userSymbol->getUser()->getUserSetting()->getUseExchange()),
                $user
            );
            $result = $this->orderManager->buy($userSymbol, 5);
            self::assertTrue($result);
        }
    }

    public function testSell(): void
    {
        $this->orderRepositoryMock
            ->expects($this->any())
            ->method('findPendingOrder')
            ->willReturn((new Order())
                ->setQuantity(374.8049)->setPrice(2.662)
                ->setSymbol($this->symbols[PriceFixture::PRICE_TO_TOP_SYMBOL])
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
                ->setSymbol($this->symbols[PriceFixture::PRICE_TO_TOP_SYMBOL])
                ->setUser($user)
            ;
            $this->orderManager->setApi(
                $this->apiFactory->build($userSymbol->getUser()->getUserSetting()->getUseExchange()),
                $user
            );
            $result = $this->orderManager->sell($userSymbol);
            self::assertTrue($result);
        }
    }

    public function testCancelUnfilledOrders(): void
    {
        $user = current($this->users);
        $this->orderManager->setApi($this->apiMock, $user);
        $this->apiMock
            ->method('cancelUnfilledOrders')
            ->willReturn([
                PriceFixture::PRICE_TO_TOP_SYMBOL => [
                    'type' => Order::STATUS_BUY,
                    'partialQuantity' => 0.4025,
                ],
            ])
        ;
        $order = (new Order())
            ->setSymbol($this->symbols[PriceFixture::PRICE_TO_TOP_SYMBOL])
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
