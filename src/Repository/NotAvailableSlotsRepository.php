<?php

namespace App\Repository;

use App\Entity\NotAvailableSlots;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NotAvailableSlots>
 *
 * @method NotAvailableSlots|null find($id, $lockMode = null, $lockVersion = null)
 * @method NotAvailableSlots|null findOneBy(array $criteria, array $orderBy = null)
 * @method NotAvailableSlots[]    findAll()
 * @method NotAvailableSlots[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NotAvailableSlotsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NotAvailableSlots::class);
    }

    public function save(NotAvailableSlots $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(NotAvailableSlots $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return NotAvailableSlots[] Returns an array of NotAvailableSlots objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('n')
//            ->andWhere('n.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('n.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?NotAvailableSlots
//    {
//        return $this->createQueryBuilder('n')
//            ->andWhere('n.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
    /**
     * @param string $date
     * @return float|int|mixed|string
     * @throws \Exception
     */
    public function findByDate(string $date): mixed
    {
        return $this->createQueryBuilder('n')
            ->where('n.start <= :date')
            ->andWhere('n.end >= :date')
            ->setParameter('date', $date)
            ->getQuery()->getResult();
    }
}
