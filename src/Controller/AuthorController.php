<?php

namespace App\Controller;

use App\Entity\Author;
use App\Repository\AuthorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class AuthorController extends AbstractController
{
    /**
     * Create new author entry
     * 
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $manager
     * @param UrlGeneratorInterface $urlgenerator
     * @return JsonResponse
     */
    #[Route('/api/author', name: 'author.post', methods: ['POST'])]
    public function createAuthor(Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, UrlGeneratorInterface $urlgenerator): JsonResponse
    {
        $author = $serializer->deserialize($request->getContent(), Author::class, "json");
        $dateNow = new DateTime();
        
        $author->setStatus('on')
        ->setCreatedAt($dateNow)
        ->setUpdatedAt($dateNow);
        $entityManager->persist($author);
        $entityManager->flush();
        
        $jsonAuthor = $serializer->serialize($author, 'json',  ['groups' => "getAll"]);
        $location = $urlgenerator->generate("author.get",  ["idAuthor" => $author->getId()], UrlGeneratorInterface::ABSOLUTE_PATH);

        return new JsonResponse($jsonAuthor, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    /**
     * Update author entry
     * 
     * @param Author $author
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $manager
     * @return JsonResponse
     */
    #[Route('/api/author/{id}', name: 'author.put', methods: ['PUT'])]
    public function updateAuthor(Author $author, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
        $updatedAuthor = $serializer->deserialize($request->getContent(), Author::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $author]);
        $updatedAuthor->setUpdatedAt(new DateTime());
        $entityManager->persist($updatedAuthor);
        $entityManager->flush();
       
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Delete author entry
     * 
     * @param Author $author
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $manager
     * @return JsonResponse
     */
    #[Route('/api/author/{id}', name: 'author.delete', methods: ['DELETE'])]
    public function deleteAuthor(Author $author, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
        $arrResponse = $request->toArray();

        $force = $arrResponse["force"];
        
        if($force){
            $entityManager->remove($author);
        }else{
            $updatedAuthor = $serializer->deserialize($request->getContent(), Author::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $author]);
            $updatedAuthor->setStatus("off");
            $updatedAuthor->setUpdatedAt(new DateTime());
            $entityManager->persist($updatedAuthor);
        }
        
        $entityManager->flush();
        
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /** 
     * Renvoie toutes les entées authors
     * 
     * @param AuthorRepository $repository
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
    #[Route('/api/author', name: 'author.getAll', methods: ['GET'])]
    public function getAllAuthors(AuthorRepository $repository, SerializerInterface $serializer): JsonResponse
    {
        $authors = $repository->findAll();
        $jsonAuthors = $serializer->serialize($authors, 'json',  ['groups' => "getAll"]);
        
        return new JsonResponse($jsonAuthors, 200, [], true);
    }

    /** 
     * Renvoie l'entée author
     * 
     * @param Author $author
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
    #[Route('/api/author/{idAuthor}', name: 'author.get', methods: ['GET'])]
    #[ParamConverter("author", options: ["id" => "idAuthor"])]
    public function getAuthor(Author $author, SerializerInterface $serializer): JsonResponse
    {
        $jsonAuthor = $serializer->serialize($author, 'json', ['groups' => "getAll"]);
      
        return new JsonResponse($jsonAuthor, 200, [], true);
    }
}
