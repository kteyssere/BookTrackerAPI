<?php

namespace App\Repository;

use App\Entity\Progression;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Progression>
 *
 * @method Progression|null find($id, $lockMode = null, $lockVersion = null)
 * @method Progression|null findOneBy(array $criteria, array $orderBy = null)
 * @method Progression[]    findAll()
 * @method Progression[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProgressionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Progression::class);
    }

    /**
    * @return Progression[] Returns an array of Progression objects
    */
    public function findByStatusOn(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :val')
            ->setParameter('val', "On")
            ->orderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }


    public function findOneByLatest($value): array
   {

    return $this->createQueryBuilder('p')
            ->join('p.persona', 'pe')
            ->join('pe.user', 'u')
            ->andWhere('u.username = :val')
            ->andWhere('p.status = :status')
            ->setParameter('val', $value)
            ->setParameter('status', 'On')
            ->orderBy('p.updatedAt', 'DESC')
            ->getQuery()
            ->setMaxResults(1)
            ->getResult()
        ;
   
   }

//    /**
//     * @return Progression[] Returns an array of Progression objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Progression
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
