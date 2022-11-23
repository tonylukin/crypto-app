<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\Symbol;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 *
 * @method Order|null find($id, $lockMode = null, $lockVersion = null)
 * @method Order|null findOneBy(array $criteria, array $orderBy = null)
 * @method Order[]    findAll()
 * @method Order[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function add(Order $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Order $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findPendingOrder(Symbol $symbol): ?Order
    {
        return $this->findOneBy(['symbol' => $symbol, 'status' => Order::STATUS_BUY], ['id' => 'DESC']);
    }

    /**
     * @return Order[]
     */
    public function getLastItemsForInterval(\DateInterval $dateInterval, ?Symbol $symbol = null, bool $soldOnly = false): array
    {
        $qb = $this->createQueryBuilder('o')
            ->where('o.createdAt >= :dateTime')
            ->setParameter('dateTime', (new \DateTimeImmutable())->sub($dateInterval))
            ->orderBy('o.id', 'ASC')
            ->addOrderBy('o.symbol')
        ;
        if ($soldOnly) {
            $qb
                ->andWhere('o.status = :status')
                ->setParameter('status', Order::STATUS_SELL)
            ;
        }
        if ($symbol !== null) {
            $qb
                ->andWhere('o.symbol = :symbol')
                ->setParameter('symbol', $symbol)
            ;
        }

        return $qb->getQuery()->getResult();
    }

    public function getLastFinishedOrder(Symbol $symbol): ?Order
    {
        $qb = $this->createQueryBuilder('o')
            ->where('o.status = :status')
            ->setParameter('status', Order::STATUS_SELL)
            ->andWhere('o.symbol = :symbol')
            ->setParameter('symbol', $symbol)
            ->orderBy('o.id', 'DESC')
            ->setMaxResults(1)
        ;
        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @return Order[]
     */
    public function getLastItemsForDates(
        ?string $dateStart = null,
        ?string $dateEnd = null,
        ?Symbol $symbol = null,
        bool $onlyCompleted = false,
    ): array {
        $qb = $this->createQueryBuilder('o')
            ->orderBy('o.id', 'ASC')
            ->addOrderBy('o.symbol')
        ;
        if ($dateStart !== null) {
            $dateFrom = new \DateTimeImmutable($dateStart);
        } else {
            $dateFrom = (new \DateTimeImmutable())->modify('-7 days');
        }
        $qb
            ->andWhere('o.createdAt >= :dateStart')
            ->setParameter('dateStart', $dateFrom)
        ;
        if ($dateEnd !== null) {
            $qb
                ->andWhere('o.createdAt <= :dateEnd')
                ->setParameter('dateEnd', new \DateTimeImmutable($dateEnd))
            ;
        }
        if ($symbol !== null) {
            $qb
                ->andWhere('o.symbol = :symbol')
                ->setParameter('symbol', $symbol)
            ;
        }
        if ($onlyCompleted) {
            $qb
                ->andWhere('o.status = :status')
                ->setParameter('status', Order::STATUS_SELL)
            ;
        }

        return $qb->getQuery()->getResult();
    }
}
