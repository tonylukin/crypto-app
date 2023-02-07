<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\Symbol;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Symbol>
 *
 * @method Symbol|null find($id, $lockMode = null, $lockVersion = null)
 * @method Symbol|null findOneBy(array $criteria, array $orderBy = null)
 * @method Symbol[]    findAll()
 * @method Symbol[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SymbolRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Symbol::class);
    }

    public function add(Symbol $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Symbol $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Symbol[]
     */
    public function getActiveList(?User $user = null): array
    {
        $qb = $this->createQueryBuilder('symbol', 'symbol.name')
            ->leftJoin('symbol.orders', 'orders')
            ->leftJoin('symbol.userSymbols', 'userSymbols')
            ->andWhere('userSymbols.active = true OR orders.status = :status')
            ->setParameter('status', Order::STATUS_BUY)
        ;
        if ($user !== null) {
            $qb
                ->andWhere('userSymbols.user = :user')
                ->setParameter('user', $user)
            ;
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param string[] $symbols
     * @return Symbol[]
     */
    public function findByName(array $symbols): array
    {
        return $this->findBy(['name' => $symbols]);
    }
}
