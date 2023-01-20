<?php

namespace App\Repository;

use App\Entity\Symbol;
use App\Entity\User;
use App\Entity\UserSymbol;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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
}
