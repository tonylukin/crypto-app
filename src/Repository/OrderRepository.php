<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\Symbol;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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

    public function findPendingOrder(User $user, Symbol $symbol): ?Order
    {
        return $this->findOneBy([
            'user' => $user,
            'symbol' => $symbol,
            'status' => Order::STATUS_BUY,
        ], ['id' => 'DESC']);
    }

    /**
     * @return Order[]
     */
    public function getLastItemsForInterval(
        User $user,
        \DateInterval $dateInterval,
        ?Symbol $symbol = null,
        bool $soldOnly = false,
    ): array {
        $qb = $this->createQueryBuilder('o')
            ->where('o.createdAt >= :dateTime')
            ->setParameter('dateTime', (new \DateTimeImmutable())->sub($dateInterval))
            ->andWhere('o.user = :user')
            ->setParameter('user', $user)
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

    public function getLastFinishedOrder(User $user, Symbol $symbol): ?Order
    {
        $qb = $this->createQueryBuilder('o')
            ->where('o.status = :status')
            ->setParameter('status', Order::STATUS_SELL)
            ->andWhere('o.symbol = :symbol')
            ->setParameter('symbol', $symbol)
            ->andWhere('o.user = :user')
            ->setParameter('user', $user)
            ->orderBy('o.id', 'DESC')
            ->setMaxResults(1)
        ;
        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @return Order[]
     */
    public function getLastItemsForDates(
        User $user,
        ?string $dateStart = null,
        ?string $dateEnd = null,
        ?Symbol $symbol = null,
        bool $onlyCompleted = false,
    ): array {
        $qb = $this->getDateIntervalQueryBuilder($user, $dateStart, $dateEnd, $onlyCompleted);
        $qb
            ->innerJoin('o.symbol', 'symbol')
            ->addSelect('symbol')
        ;
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

    /**
     * @return array{count: int, sum: float, name: string}
     */
    public function getSymbolCountsForDates(
        User $user,
        ?string $dateStart = null,
        ?string $dateEnd = null,
        bool $onlyCompleted = false,
    ): array {
        $qb = $this->getDateIntervalQueryBuilder($user, $dateStart, $dateEnd, $onlyCompleted);
        $qb
            ->select('COUNT(o.id) as count, SUM(o.profit) as sum, symbol.name')
            ->innerJoin('o.symbol', 'symbol')
        ;
        if ($onlyCompleted) {
            $qb
                ->andWhere('o.status = :status')
                ->setParameter('status', Order::STATUS_SELL)
            ;
        }
        $qb->groupBy('o.symbol');

        return $qb->getQuery()->getArrayResult();
    }

    private function getDateIntervalQueryBuilder(
        User $user,
        ?string $dateStart = null,
        ?string $dateEnd = null,
        bool $onlyCompleted = false,
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('o')
            ->orderBy('o.sellDate', 'DESC')
            ->addOrderBy('o.id', 'DESC')
        ;
        if ($dateStart !== null) {
            $dateFrom = new \DateTimeImmutable($dateStart);
        } else {
            $dateFrom = (new \DateTimeImmutable())->modify('-7 days');
        }
        $qb
            ->andWhere('IF(o.sellDate IS NULL, o.createdAt, o.sellDate) >= :dateStart')
            ->setParameter('dateStart', $dateFrom)
            ->andWhere('o.user = :user')
            ->setParameter('user', $user)
        ;
        if ($dateEnd !== null) {
            $qb
                ->andWhere('IF(o.sellDate IS NULL, o.createdAt, o.sellDate) <= :dateEnd')
                ->setParameter('dateEnd', new \DateTimeImmutable($dateEnd))
            ;
        }

        return $qb;
    }
}
