<?php

namespace App\Repository;

use App\Entity\Price;
use App\Entity\Symbol;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Price>
 *
 * @method Price|null find($id, $lockMode = null, $lockVersion = null)
 * @method Price|null findOneBy(array $criteria, array $orderBy = null)
 * @method Price[]    findAll()
 * @method Price[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PriceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Price::class);
    }

    public function add(Price $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Price $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Price[]
     */
    public function getLastItemsForInterval(\DateInterval $dateInterval, ?Symbol $symbol = null, string $sortDirection = 'ASC'): array
    {
        $qb = $this->createQueryBuilder('price')
            ->where('price.datetime >= :dateTime')
            ->setParameter('dateTime', (new \DateTimeImmutable())->sub($dateInterval))
            ->orderBy('price.datetime', $sortDirection)
            ->addOrderBy('price.symbol')
        ;
        if ($symbol !== null) {
            $qb
                ->andWhere('price.symbol = :symbol')
                ->setParameter('symbol', $symbol)
            ;
        }

        return $qb->getQuery()->getResult();
    }

    public function getAvgForInterval(\DateInterval $dateInterval, Symbol $symbol): ?float
    {
        $qb = $this->createQueryBuilder('price')
            ->select('AVG(price.price) AS avgPrice')
            ->where('price.datetime >= :dateTime')
            ->setParameter('dateTime', (new \DateTimeImmutable())->sub($dateInterval))
            ->andWhere('price.symbol = :symbol')
            ->setParameter('symbol', $symbol)
        ;

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getLastItemsForDates(
        ?string $dateStart = null,
        ?string $dateEnd = null,
        ?Symbol $symbol = null,
    ): array {
        $qb = $this->createQueryBuilder('price')
            ->orderBy('price.id', 'ASC')
            ->addOrderBy('price.symbol')
        ;
        if ($dateStart !== null) {
            $dateFrom = new \DateTimeImmutable($dateStart);
        } else {
            $dateFrom = (new \DateTimeImmutable())->modify('-7 days');
        }
        $qb
            ->andWhere('price.datetime >= :dateStart')
            ->setParameter('dateStart', $dateFrom)
        ;
        if ($dateEnd !== null) {
            $qb
                ->andWhere('price.datetime <= :dateEnd')
                ->setParameter('dateEnd', new \DateTimeImmutable($dateEnd))
            ;
        }
        if ($symbol !== null) {
            $qb
                ->andWhere('price.symbol = :symbol')
                ->setParameter('symbol', $symbol)
            ;
        }

        return $qb->getQuery()->getResult();
    }

    public function getLastHighPrice(Symbol $symbol, \DateInterval $dateInterval): ?float
    {
        $qb = $this->createQueryBuilder('price')
            ->select('MAX(price.price) AS highPrice')
            ->where('price.datetime >= :dateTime')
            ->setParameter('dateTime', (new \DateTimeImmutable())->sub($dateInterval))
            ->andWhere('price.symbol = :symbol')
            ->setParameter('symbol', $symbol)
        ;

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getLastMinPrice(Symbol $symbol, \DateInterval $dateInterval): ?float
    {
        $qb = $this->createQueryBuilder('price')
            ->select('MIN(price.price) AS highPrice')
            ->where('price.datetime >= :dateTime')
            ->setParameter('dateTime', (new \DateTimeImmutable())->sub($dateInterval))
            ->andWhere('price.symbol = :symbol')
            ->setParameter('symbol', $symbol)
        ;

        return $qb->getQuery()->getSingleScalarResult();
    }
}
