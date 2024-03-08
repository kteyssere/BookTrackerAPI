<?php

namespace App\Controller;

use App\Entity\Book;
use App\Repository\BookRepository;
use App\Entity\Picture;
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
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class BookController extends AbstractController
{

    /**
     * Create new book entry
     * 
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $manager
     * @param UrlGeneratorInterface $urlgenerator
     * @param ValidatorInterface $validator
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[Route('/api/book', name: 'book.post', methods: ['POST'])]
    #[IsGranted("ADMIN")]

    public function createBook(Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, UrlGeneratorInterface $urlgenerator, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse
    {
        $book = $serializer->deserialize($request->getContent(), Book::class, "json");
        $dateNow = new DateTime();
        
        $book->setStatus('on')
        ->setCreatedAt($dateNow)
        ->setUpdatedAt($dateNow);

        $picture = new Picture();
        $file = $request->files->get('file');
        
        $picture->setFile($file);
        $picture->setMimeType($file->getClientMimeType());
        $picture->setRealName($file->getClientOriginalName());
        $picture->setName($file->getClientOriginalName());
        $picture->setPublicPath('/public/medias/pictures');
        $picture->setStatus('on')
        ->setCreatedAt(new DateTime())
        ->setUpdatedAt(new DateTime());
        $entityManager->persist($picture);

        $book->setCoverImage($picture);
        
        $errors = $validator->validate($book);
        if($errors->count() > 1){
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $entityManager->persist($book);
        $entityManager->flush();
        
        $cache->invalidateTags(["bookCache"]);

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
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[Route('/api/book/{id}', name: 'book.put', methods: ['PUT'])]
    #[IsGranted("ADMIN")]

    public function updateBook(Book $book, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, TagAwareCacheInterface $cache): JsonResponse
    {
        $updatedBook = $serializer->deserialize($request->getContent(), Book::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $book]);
        $updatedBook->setUpdatedAt(new DateTime());
        $entityManager->persist($updatedBook);
        $entityManager->flush();

        $cache->invalidateTags(["bookCache"]);

       
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Delete book entry
     * 
     * @param Book $book
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $manager
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[Route('/api/book/{id}', name: 'book.delete', methods: ['DELETE'])]
    #[IsGranted("ADMIN")]

    public function deleteBook(Book $book, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, TagAwareCacheInterface $cache): JsonResponse
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

        $cache->invalidateTags(["bookCache"]);

        
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /** 
     * Renvoie toutes les entées books
     * 
     * @param BookRepository $repository
     * @param SerializerInterface $serializer
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[OA\Response(
        response:200,
        description: "Retourne la liste des livres",
        content: new OA\JsonContent(
            type: "array",
            items: new OA\Items(ref: new Model(type:Book::class))
        )
    )]
    #[Route('/api/book', name: 'book.getAll', methods: ['GET'])]
    public function getAllBooks(BookRepository $repository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        $idCache = "getAllBook";
        $cache->invalidateTags(["bookCache"]);
        $jsonBooks = $cache->get($idCache, function (ItemInterface $item) use ($repository, $serializer) {
            $item->tag("bookCache");
            $books = $repository->findAll();
            return $serializer->serialize($books, 'json',  ['groups' => "getAll"]);
        });
        
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
