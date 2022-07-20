<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\Symbol;
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
    public function getActiveList(): array
    {
        return $this->createQueryBuilder('symbol', 'symbol.name')
            ->leftJoin('symbol.orders', 'orders')
            ->where('symbol.active = true')
            ->orWhere('orders.status = :status')
            ->setParameter('status', Order::STATUS_BUY)
            ->getQuery()
            ->getResult()
        ;
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
