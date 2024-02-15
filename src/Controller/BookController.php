<?php

namespace App\Controller;

use App\Entity\Book;
use App\Repository\BookRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BookController extends AbstractController
{

    /**
     * Create new book entry
     * 
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $manager
     * @param UrlGeneratorInterface $urlgenerator
     * @return JsonResponse
     */
    #[Route('/api/book', name: 'book.post', methods: ['POST'])]
    public function createBook(Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, UrlGeneratorInterface $urlgenerator, ValidatorInterface $validator): JsonResponse
    {
        $book = $serializer->deserialize($request->getContent(), Book::class, "json");
        $dateNow = new DateTime();
        
        $book->setStatus('on')
        ->setCreatedAt($dateNow)
        ->setUpdatedAt($dateNow);

        $errors = $validator->validate($book);
        if($errors->count() > 1){
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $entityManager->persist($book);
        $entityManager->flush();
        
        $jsonBook = $serializer->serialize($book, 'json',  ['groups' => "getAll"]);
        $location = $urlgenerator->generate("book.get",  ["idBook" => $book->getId()], UrlGeneratorInterface::ABSOLUTE_PATH);
        return new JsonResponse($jsonBook, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    /**
     * Update book entry
     * 
     * @param Book $book
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $manager
     * @return JsonResponse
     */
    #[Route('/api/book/{id}', name: 'book.put', methods: ['PUT'])]
    public function updateBook(Book $book, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
        $updatedBook = $serializer->deserialize($request->getContent(), Book::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $book]);
        $updatedBook->setUpdatedAt(new DateTime());
        $entityManager->persist($updatedBook);
        $entityManager->flush();
       
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Delete book entry
     * 
     * @param Book $book
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $manager
     * @return JsonResponse
     */
    #[Route('/api/book/{id}', name: 'book.delete', methods: ['DELETE'])]
    public function deleteBook(Book $book, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
        $arrResponse = $request->toArray();

        $force = $arrResponse["force"];
        
        if($force){
            $entityManager->remove($book);
        }else{
            $updatedBook = $serializer->deserialize($request->getContent(), Book::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $book]);
            $updatedBook->setStatus("off");
            $updatedBook->setUpdatedAt(new DateTime());
            $entityManager->persist($updatedBook);
        }
        
        $entityManager->flush();
        
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /** 
     * Renvoie toutes les entées books
     * 
     * @param BookRepository $repository
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
    #[Route('/api/book', name: 'book.getAll', methods: ['GET'])]
    public function getAllBooks(BookRepository $repository, SerializerInterface $serializer): JsonResponse
    {
        $books = $repository->findAll();
        $jsonBooks = $serializer->serialize($books, 'json',  ['groups' => "getAll"]);
        
        return new JsonResponse($jsonBooks, 200, [], true);
    }

    /** 
     * Renvoie l'entée book
     * 
     * @param Book $book
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
    #[Route('/api/book/{idBook}', name: 'book.get', methods: ['GET'])]
    #[ParamConverter("book", options: ["id" => "idBook"])]
    public function getBook(Book $book, SerializerInterface $serializer): JsonResponse
    {
        $jsonBook = $serializer->serialize($book, 'json', ['groups' => "getAll"]);

        return new JsonResponse($jsonBook, 200, [], true);
    }
}
