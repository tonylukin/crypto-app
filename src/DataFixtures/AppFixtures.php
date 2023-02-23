<?php

namespace App\DataFixtures;

use App\Entity\Order;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Nzo\UrlEncryptorBundle\Encryptor\Encryptor;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $userPasswordHasher,
        private EntityManagerInterface $entityManager,
        private Encryptor $encryptor,
    )
    {}

    public function load(ObjectManager $manager): void
    {
        $user = (new User())
            ->setUsername('admin')
            ->setRoles([User::ROLE_ADMIN])
        ;
        $user->setPassword($this->userPasswordHasher->hashPassword($user, '28051989'));
        $user->getUserSetting()->setBinanceApiKey('123');
        $user->getUserSetting()->setBinanceApiSecret($this->encryptor->encrypt('123456'));
        $manager->persist($user);

        $user = (new User())
            ->setUsername('user')
        ;
        $user->setPassword($this->userPasswordHasher->hashPassword($user, '28051989'));
        $user->getUserSetting()->setUseExchange(Order::EXCHANGE_HUOBI);
        $user->getUserSetting()->setHuobiApiKey('123');
        $user->getUserSetting()->setHuobiApiSecret($this->encryptor->encrypt('123456'));
        $manager->persist($user);

        $manager->flush();

        $cronJobs = [
            [
                'name' => 'trade',
                'command' => 'app:trade',
                'schedule' => '*/10 * * * *',
                'description' => 'Buy and sale crypto',
                'enabled' => 1,
            ],
            [
                'name' => 'prices',
                'command' => 'app:import-prices',
//                'schedule' => '8,18,28,38,48,58 * * * *',
                'schedule' => '0,30 * * * *',
                'description' => 'Save prices from exchange',
                'enabled' => 1,
            ],
            [
                'name' => 'cleanUp',
                'command' => 'app:clean-up',
                'schedule' => '0 14 * * 5',
                'description' => 'Clean old database records',
                'enabled' => 1,
            ],
        ];

        foreach ($cronJobs as $cronJob) {
            $this->entityManager->getConnection()->insert('cron_job', $cronJob);
        }
    }
}
