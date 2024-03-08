<?php

namespace App\Controller;

use App\Entity\Genre;
use App\Repository\GenreRepository;
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
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;


class GenreController extends AbstractController
{
    /**
     * Create new genre entry
     * 
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $manager
     * @param UrlGeneratorInterface $urlgenerator
     * @param ValidatorInterface $validator
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[Route('/api/genre', name: 'genre.post', methods: ['POST'])]
    #[IsGranted("ADMIN")]

    public function createGenre(Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, UrlGeneratorInterface $urlgenerator, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse
    {
        $genre = $serializer->deserialize($request->getContent(), Genre::class, "json");
        $dateNow = new DateTime();
        
        $genre->setStatus('on')
        ->setCreatedAt($dateNow)
        ->setUpdatedAt($dateNow);
        
        $errors = $validator->validate($genre);
        if($errors->count() > 1){
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
        
        $entityManager->persist($genre);
        $entityManager->flush();

        $cache->invalidateTags(["genreCache"]);

        $jsonGenre = $serializer->serialize($genre, 'json',  ['groups' => "getAll"]);
        $location = $urlgenerator->generate("genre.get",  ["idGenre" => $genre->getId()], UrlGeneratorInterface::ABSOLUTE_PATH);

        return new JsonResponse($jsonGenre, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    /**
     * Update genre entry
     * 
     * @param Genre $genre
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $manager
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[Route('/api/genre/{id}', name: 'genre.put', methods: ['PUT'])]
    #[IsGranted("ADMIN")]

    public function updateGenre(Genre $genre, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, TagAwareCacheInterface $cache): JsonResponse
    {
        $updatedGenre = $serializer->deserialize($request->getContent(), Genre::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $genre]);
        $updatedGenre->setUpdatedAt(new DateTime());
        $entityManager->persist($updatedGenre);
        $entityManager->flush();

        $cache->invalidateTags(["genreCache"]);

       
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Delete genre entry
     * 
     * @param Genre $genre
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $manager
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[Route('/api/genre/{id}', name: 'genre.delete', methods: ['DELETE'])]
    #[IsGranted("ADMIN")]

    public function deleteGenre(Genre $genre, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, TagAwareCacheInterface $cache): JsonResponse
    {
        $arrResponse = $request->toArray();

        $force = $arrResponse["force"];
        
        if($force){
            $entityManager->remove($genre);
        }else{
            $updatedGenre = $serializer->deserialize($request->getContent(), Genre::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $genre]);
            $updatedGenre->setStatus("off");
            $updatedGenre->setUpdatedAt(new DateTime());
            $entityManager->persist($updatedGenre);
        }
        
        $entityManager->flush();

        $cache->invalidateTags(["genreCache"]);

        
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /** 
     * Renvoie toutes les entées genres
     * 
     * @param GenreRepository $repository
     * @param SerializerInterface $serializer
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[OA\Response(
        response:200,
        description: "Retourne la liste des genres",
        content: new OA\JsonContent(
            type: "array",
            items: new OA\Items(ref: new Model(type:Genre::class))
        )
    )]
    #[Route('/api/genre', name: 'genre.getAll', methods: ['GET'])]
    public function getAllGenres(GenreRepository $repository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        $idCache = "getAllGenre";
        $cache->invalidateTags(["genreCache"]);
        $jsonGenres = $cache->get($idCache, function (ItemInterface $item) use ($repository, $serializer) {
            $item->tag("genreCache");
            $genres = $repository->findAll();
            return $serializer->serialize($genres, 'json',  ['groups' => "getAll"]);
        });

        return new JsonResponse($jsonGenres, 200, [], true);
    }

    /** 
     * Renvoie l'entée genre
     * 
     * @param Genre $genre
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
    #[Route('/api/genre/{idGenre}', name: 'genre.get', methods: ['GET'])]
    #[ParamConverter("genre", options: ["id" => "idGenre"])]
    public function getGenre(Genre $genre, SerializerInterface $serializer): JsonResponse
    {
        $jsonGenre = $serializer->serialize($genre, 'json', ['groups' => "getAll"]);
      
        return new JsonResponse($jsonGenre, 200, [], true);
    }
}
