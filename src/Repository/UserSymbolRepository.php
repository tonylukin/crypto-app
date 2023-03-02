<?php

namespace App\Repository;

use App\Entity\Symbol;
use App\Entity\User;
use App\Entity\UserSymbol;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<UserSymbol>
 *
 * @method UserSymbol|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserSymbol|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserSymbol[]    findAll()
 * @method UserSymbol[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserSymbolRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserSymbol::class);
    }

    public function add(UserSymbol $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(UserSymbol $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneBySymbolAndUser(User $user, Symbol $symbol): ?UserSymbol
    {
        return $this->createQueryBuilder('us')
            ->innerJoin('us.symbol', 'symbol')
            ->addSelect('symbol')
            ->andWhere('us.user = :user')
            ->setParameter('user', $user)
            ->andWhere('us.symbol = :symbol')
            ->setParameter('symbol', $symbol)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * @return UserSymbol[]
     */
    public function findAllByUser(User $user): array
    {
        return $this->createQueryBuilder('us')
            ->innerJoin('us.symbol', 'symbol')
            ->addSelect('symbol')
            ->andWhere('us.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult()
        ;
    }

    public function batchChangeTotalPrice(array $symbolIds, UserInterface $user, float $totalPrice): int
    {
        if (empty($symbolIds)) {
            return 0;
        }

        return $this->createQueryBuilder('us')
            ->update()
            ->set('us.totalPrice', $totalPrice)
            ->where('us.user = :user')
            ->setParameter('user', $user)
            ->andWhere('IDENTITY(us.symbol) IN (:symbolIds)')
            ->setParameter('symbolIds', $symbolIds)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function batchToggleActive(array $symbolIds, UserInterface $user): int
    {
        if (empty($symbolIds)) {
            return 0;
        }

        return $this->createQueryBuilder('us')
            ->update()
            ->set('us.active', '1 - us.active')
            ->where('us.user = :user')
            ->setParameter('user', $user)
            ->andWhere('IDENTITY(us.symbol) IN (:symbolIds)')
            ->setParameter('symbolIds', $symbolIds)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
