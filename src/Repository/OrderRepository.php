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

    public function getLastOrder(User $user, Symbol $symbol, ?string $status = null): ?Order
    {
        $qb = $this->createQueryBuilder('o')
            ->andWhere('o.symbol = :symbol')
            ->setParameter('symbol', $symbol)
            ->andWhere('o.user = :user')
            ->setParameter('user', $user)
            ->orderBy('o.id', 'DESC')
            ->setMaxResults(1)
        ;
        if ($status !== null) {
            $qb
                ->andWhere('o.status = :status')
                ->setParameter('status', $status)
            ;
        }
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
            ->innerJoin('symbol.userSymbols', 'userSymbol', 'WITH', 'userSymbol.user = :user')
            ->addSelect('userSymbol')
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
            ->select('COUNT(o.id) as count, SUM(o.profit) as sum, AVG(DATEDIFF(o.sellDate, o.createdAt) + 1) as avgDays, symbol.name, userSymbol.active')
            ->innerJoin('o.symbol', 'symbol')
            ->innerJoin('symbol.userSymbols', 'userSymbol', 'WITH', 'userSymbol.user = :user')
        ;
        if ($onlyCompleted) {
            $qb
                ->andWhere('o.status = :status')
                ->setParameter('status', Order::STATUS_SELL)
            ;
        }
        $qb->groupBy('o.symbol')->orderBy('sum', 'DESC');

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
            $dateFrom = new \DateTimeImmutable('first day of this month');
        }
        $dateFrom->setTime(0, 0);
        $qb
            ->andWhere('IF(o.sellDate IS NULL, o.createdAt, o.sellDate) >= :dateStart')
            ->setParameter('dateStart', $dateFrom)
            ->andWhere('o.user = :user')
            ->setParameter('user', $user)
        ;
        if ($dateEnd !== null) {
            $qb
                ->andWhere('IF(o.sellDate IS NULL, o.createdAt, o.sellDate) <= :dateEnd')
                ->setParameter('dateEnd', (new \DateTimeImmutable($dateEnd))->setTime(23, 59))
            ;
        }

        return $qb;
    }
}
