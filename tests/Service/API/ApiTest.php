<?php

declare(strict_types=1);

namespace App\Tests\Service\API;

use App\Entity\Order;
use App\Entity\User;
use App\Entity\UserSetting;
use App\Service\ApiFactory;
use App\Service\ApiInterface;
use App\Service\Binance\API;
use Nzo\UrlEncryptorBundle\Encryptor\Encryptor;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ApiTest extends KernelTestCase
{
    private ApiFactory $apiFactory;
    private Encryptor $encryptor;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        $container = static::getContainer();
        $this->apiFactory = $container->get(ApiFactory::class);
        $this->encryptor = $container->get(Encryptor::class);
    }

    /**
     * @dataProvider getUserSettingsDataProvider
     */
    public function testCheckOrderStatus(UserSetting $userSetting): void
    {
        $user = new User();
        $user->setUserSetting($userSetting);
        /** @var ApiInterface|API|\App\Service\Huobi\API $api */
        $api = $this->apiFactory->build($userSetting->getUseExchange());
        if ($userSetting->getUseExchange() === Order::EXCHANGE_BINANCE) {
            $api->useServerTime();
            $userSetting->setBinanceApiSecret($this->encryptor->encrypt($_ENV['BINANCE_API_SECRET']));
        } else {
            $userSetting->setHuobiApiSecret($this->encryptor->encrypt($_ENV['HUOBI_API_SECRET_KEY']));
        }
        $api->setCredentials($user);

        $result = $api->cancelUnfilledOrders();
        if (empty($result)) {
            $this->markTestIncomplete();
        }

        self::assertArrayHasKey('type', current($result));
    }

    public function getUserSettingsDataProvider(): \Generator
    {
        yield [(new UserSetting())->setBinanceApiKey($_ENV['BINANCE_API_KEY'])];
        yield [(new UserSetting())->setHuobiApiKey($_ENV['HUOBI_API_ACCESS_KEY'])->setUseExchange(Order::EXCHANGE_HUOBI)];
    }
}
