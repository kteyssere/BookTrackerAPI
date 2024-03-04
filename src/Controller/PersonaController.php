<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;


use App\Entity\Persona;
use App\Entity\User;
use App\Repository\PersonaRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;


class PersonaController extends AbstractController
{
     /**
     * Create new persona entry
     * 
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $manager
     * @param UrlGeneratorInterface $urlgenerator
     * @return JsonResponse
     */
    #[Route('/api/persona', name: 'persona.post', methods: ['POST'])]
    public function createPersona(Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, UrlGeneratorInterface $urlgenerator, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse
    {
        $persona = $serializer->deserialize($request->getContent(), Persona::class, "json");
        $dateNow = new DateTime();
        
        $persona->setStatus('on')
        ->setAnonymous(false)
        ->setCreatedAt($dateNow)
        ->setUpdatedAt($dateNow);

        $errors = $validator->validate($persona);
        if($errors->count() > 1){
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $entityManager->persist($persona);
        $rawPassword = $request->getContent();
        dd($rawPassword);
        // $user  = new User();
        // $user->setUsername($persona->getUserName())
        // ->setRoles(["USER"])
        // ->setPassword()
        // ->setPersona($persona);

        //$entityManager->persist($user);


        $entityManager->flush();
        
        $cache->invalidateTags(["personaCache"]);

        $jsonPersona = $serializer->serialize($persona, 'json',  ['groups' => "getAll"]);
        $location = $urlgenerator->generate("persona.get",  ["idPersona" => $persona->getId()], UrlGeneratorInterface::ABSOLUTE_PATH);
        return new JsonResponse($jsonPersona, Response::HTTP_CREATED, ["Location" => $location], true);
    }

//     /**
//      * Update book entry
//      * 
//      * @param Book $book
//      * @param Request $request
//      * @param SerializerInterface $serializer
//      * @param EntityManagerInterface $manager
//      * @return JsonResponse
//      */
//     #[Route('/api/book/{id}', name: 'book.put', methods: ['PUT'])]
//     public function updateBook(Book $book, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, TagAwareCacheInterface $cache): JsonResponse
//     {
//         $updatedBook = $serializer->deserialize($request->getContent(), Book::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $book]);
//         $updatedBook->setUpdatedAt(new DateTime());
//         $entityManager->persist($updatedBook);
//         $entityManager->flush();

//         $cache->invalidateTags(["bookCache"]);

       
//         return new JsonResponse(null, Response::HTTP_NO_CONTENT);
//     }

//     /**
//      * Delete book entry
//      * 
//      * @param Book $book
//      * @param Request $request
//      * @param SerializerInterface $serializer
//      * @param EntityManagerInterface $manager
//      * @return JsonResponse
//      */
//     #[Route('/api/book/{id}', name: 'book.delete', methods: ['DELETE'])]
//     public function deleteBook(Book $book, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, TagAwareCacheInterface $cache): JsonResponse
//     {
//         $arrResponse = $request->toArray();

//         $force = $arrResponse["force"];
        
//         if($force){
//             $entityManager->remove($book);
//         }else{
//             $updatedBook = $serializer->deserialize($request->getContent(), Book::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $book]);
//             $updatedBook->setStatus("off");
//             $updatedBook->setUpdatedAt(new DateTime());
//             $entityManager->persist($updatedBook);
//         }
        
//         $entityManager->flush();

//         $cache->invalidateTags(["bookCache"]);

        
//         return new JsonResponse(null, Response::HTTP_NO_CONTENT);
//     }

//     /** 
//      * Renvoie toutes les entées books
//      * 
//      * @param BookRepository $repository
//      * @param SerializerInterface $serializer
//      * @return JsonResponse
//      */
//     #[Route('/api/book', name: 'book.getAll', methods: ['GET'])]
//    // #[IsGranted("IS_AUTHENTIFICATED_FULLY")]
//     public function getAllBooks(BookRepository $repository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
//     {
//         $idCache = "getAllBook";
//         $cache->invalidateTags(["bookCache"]);
//         $jsonBooks = $cache->get($idCache, function (ItemInterface $item) use ($repository, $serializer) {
//             //echo "MISE EN CACHE";
//             $item->tag("bookCache");
//             $books = $repository->findAll();
//             return $serializer->serialize($books, 'json',  ['groups' => "getAll"]);
//         });
        
//         return new JsonResponse($jsonBooks, 200, [], true);
//     }

//     /** 
//      * Renvoie l'entée book
//      * 
//      * @param Book $book
//      * @param SerializerInterface $serializer
//      * @return JsonResponse
//      */
//     #[Route('/api/book/{idBook}', name: 'book.get', methods: ['GET'])]
//     #[ParamConverter("book", options: ["id" => "idBook"])]
//     public function getBook(Book $book, SerializerInterface $serializer): JsonResponse
//     {
//         $jsonBook = $serializer->serialize($book, 'json', ['groups' => "getAll"]);

//         return new JsonResponse($jsonBook, 200, [], true);
//     }
}