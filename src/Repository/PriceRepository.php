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
    public function getLastItemsForInterval(\DateInterval $dateInterval, ?Symbol $symbol = null): array
    {
        $qb = $this->createQueryBuilder('price')
            ->where('price.datetime >= :dateTime')
            ->setParameter('dateTime', (new \DateTimeImmutable())->sub($dateInterval))
            ->orderBy('price.datetime', 'DESC')
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
}
