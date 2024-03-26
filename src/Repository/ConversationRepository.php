<?php

namespace App\Repository;

use App\Entity\Conversation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Conversation>
 *
 * @method Conversation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Conversation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Conversation[]    findAll()
 * @method Conversation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

    /**
    * @return Conversation[] Returns an array of Conversation objects
    */
   public function findAllOfUser($value): array
   {
       return $this->createQueryBuilder('c')
           ->join('c.participants', 'p')
           ->join('c.messages', 'm')
           ->andWhere('p.user = :val')
           ->andWhere('c.status = on')
           ->setParameter('val', $value)
           ->orderBy('m.createdAt', 'ASC')
           ->getQuery()
           ->getResult()
       ;
   }

    /**
    * @return Conversation[] Returns an array of Conversation objects
    */
    public function findByStatusOn(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.status = :val')
            ->setParameter('val', "On")
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

//    /**
//     * @return Conversation[] Returns an array of Conversation objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Conversation
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
