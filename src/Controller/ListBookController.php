<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

use App\Entity\ListBook;
use App\Repository\ListBookRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class ListBookController extends AbstractController
{
    /**
     * Create new listBook entry
     * 
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $manager
     * @param UrlGeneratorInterface $urlgenerator
     * @param ValidatorInterface $validator
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[Route('/api/list-book', name: 'listBook.post', methods: ['POST'])]
    public function createListBook(Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, UrlGeneratorInterface $urlgenerator, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse
    {
        $listBook = $serializer->deserialize($request->getContent(), ListBook::class, "json");
        $dateNow = new DateTime();
        
        $listBook->setStatus('on')
        ->setCreatedAt($dateNow)
        ->setUpdatedAt($dateNow);

        $errors = $validator->validate($listBook);
        if($errors->count() > 0){
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $entityManager->persist($listBook);
        $entityManager->flush();

        $cache->invalidateTags(["listBookCache"]);
        
        $jsonListBook = $serializer->serialize($listBook, 'json',  ['groups' => "getAll"]);
        $location = $urlgenerator->generate("listBook.get",  ["idListBook" => $listBook->getId()], UrlGeneratorInterface::ABSOLUTE_PATH);

        return new JsonResponse($jsonListBook, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    /**
     * Update listBook entry
     * 
     * @param ListBook $listBook
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $manager
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[Route('/api/list-book/{id}', name: 'listBook.put', methods: ['PUT'])]
    public function updateListBook(ListBook $listBook, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, TagAwareCacheInterface $cache): JsonResponse
    {
        $updatedListBook = $serializer->deserialize($request->getContent(), ListBook::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $listBook]);
        $updatedListBook->setUpdatedAt(new DateTime());
        $entityManager->persist($updatedListBook);
        $entityManager->flush();

        $cache->invalidateTags(["listBookCache"]);

       
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Delete listBook entry
     * 
     * @param ListBook $listBook
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $manager
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[Route('/api/list-book/{id}', name: 'listBook.delete', methods: ['DELETE'])]
    public function deleteListBook(ListBook $listBook, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, TagAwareCacheInterface $cache): JsonResponse
    {
        $arrResponse = $request->toArray();

        $force = $arrResponse["force"];
        
        if($force){
            $entityManager->remove($listBook);
        }else{
            $updatedListBook = $serializer->deserialize($request->getContent(), ListBook::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $listBook]);
            $updatedListBook->setStatus("off");
            $updatedListBook->setUpdatedAt(new DateTime());
            $entityManager->persist($updatedListBook);
        }
        
        $entityManager->flush();

        $cache->invalidateTags(["listBookCache"]);
        
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /** 
     * Renvoie toutes les entées listBooks
     * 
     * @param ListBookRepository $repository
     * @param SerializerInterface $serializer
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[OA\Response(
        response:200,
        description: "Retourne la liste des etageres",
        content: new OA\JsonContent(
            type: "array",
            items: new OA\Items(ref: new Model(type:ListBook::class))
        )
    )]
    #[Route('/api/list-book', name: 'listBook.getAll', methods: ['GET'])]
    public function getAllListBooks(ListBookRepository $repository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        $idCache = "getAllListBook";
        $cache->invalidateTags(["listBookCache"]);
        $jsonListBooks = $cache->get($idCache, function (ItemInterface $item) use ($repository, $serializer) {
            $item->tag("listBookCache");
            $listBooks = $repository->findByPersona($this->getUser()->getUserIdentifier());
            return $serializer->serialize($listBooks, 'json',  ['groups' => "getAllListBooks"]);
        });

        return new JsonResponse($jsonListBooks, 200, [], true);
    }

    /** 
     * Renvoie l'entée listBook
     * 
     * @param ListBook $listBook
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
    #[Route('/api/list-book/{idListBook}', name: 'listBook.get', methods: ['GET'])]
    #[ParamConverter("listBook", options: ["id" => "idListBook"])]
    public function getListBook(ListBook $listBook, SerializerInterface $serializer): JsonResponse
    {
        $jsonListBook = $serializer->serialize($listBook, 'json', ['groups' => "getAll"]);
      
        return new JsonResponse($jsonListBook, 200, [], true);
    }
}
