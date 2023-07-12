<?php

namespace App\Repository;

use App\Entity\LastPrice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LastPrice>
 *
 * @method LastPrice|null find($id, $lockMode = null, $lockVersion = null)
 * @method LastPrice|null findOneBy(array $criteria, array $orderBy = null)
 * @method LastPrice[]    findAll()
 * @method LastPrice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LastPriceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LastPrice::class);
    }

    public function save(LastPrice $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(LastPrice $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return LastPrice[] Returns an array of LastPrice objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('l.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?LastPrice
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
