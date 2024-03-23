<?php

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Book>
 *
 * @method Book|null find($id, $lockMode = null, $lockVersion = null)
 * @method Book|null findOneBy(array $criteria, array $orderBy = null)
 * @method Book[]    findAll()
 * @method Book[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }


    /**
    * @return Book[] Returns an array of Book objects
    */
   public function findByQuery($value): array
   {
        return $this->createQueryBuilder('b')
            ->join('b.authors','a')
            ->join('b.categories','c')
            ->orWhere('b.title = :val')
            ->orWhere('b.isbn13 = :val')
            ->orWhere('b.isbn10 = :val')
            ->orWhere('a.name = :val')
            ->orWhere('c.name = :val')
            ->setParameter('val', $value)
            ->orderBy('b.title', 'ASC')
            ->getQuery()
            ->getResult()
            ;
   }


    /**
    * @return Book[] Returns an array of Book objects
    */
    public function findByCategory($value): array
    {
        return $this->createQueryBuilder('b')
            ->join('b.categories','c')
            ->where('c.name = :val')
            ->setParameter('val', $value)
            ->orderBy('b.title', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }

    /**
    * @return Book[] Returns an array of Book objects
    */
    public function findByAuthor($value): array
    {
        return $this->createQueryBuilder('b')
        ->join('b.authors','a')
        ->where('a.name = :val')
        ->setParameter('val', $value)
        ->orderBy('b.title', 'ASC')
        ->getQuery()
        ->getResult()
        ;
       
    }

    /**
    * @return Book[] Returns an array of Book objects
    */
   public function findByStatus($value): array
   {
       return $this->createQueryBuilder('b')
           ->andWhere('b.status = :val')
           ->setParameter('val', $value)
           ->orderBy('b.id', 'ASC')
           ->getQuery()
           ->getResult()
       ;
   }

   /**
    * @return Book[] Returns an array of Book objects
    */
    public function findByStatusOn(): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.status = :val')
            ->setParameter('val', "On")
            ->orderBy('b.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
    * @return Book[] Returns an array of Book objects
    */
    public function findByStatusOff(): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.status = :val')
            ->setParameter('val', "Off")
            ->orderBy('b.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

//    /**
//     * @return Book[] Returns an array of Book objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('b.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Book
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
