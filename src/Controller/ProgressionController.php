<?php

namespace App\Controller;

use App\Entity\Progression;
use App\Repository\ProgressionRepository;
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
use Symfony\Component\Security\Core\Security;

class ProgressionController extends AbstractController
{

        /** 
     * Renvoie la derniere entée progression
     * 
     * @param ProgressionRepository $repository
     * @param SerializerInterface $serializer
     * @param TagAwareCacheInterface $cache
     * @param Security $security
     * @return JsonResponse
     */
    #[Route('/api/progression/latest', name: 'progression.getLatest', methods: ['GET'])]
    public function getLatestProgression(ProgressionRepository $repository, SerializerInterface $serializer, TagAwareCacheInterface $cache, Security $security): JsonResponse
    {
        $idCache = "getLatestProgression";
        $cache->invalidateTags(["latestProgressionCache"]);
        $jsonProgression = $cache->get($idCache, function (ItemInterface $item) use ($repository, $serializer, $security) {
            $item->tag("latestProgressionCache");
            $user = $security->getUser();
            $progressions = $repository->findOneByLatest($user->getUserIdentifier());
            return $serializer->serialize($progressions, 'json',  ['groups' => "getAll"]);
        });
        
        return new JsonResponse($jsonProgression, 200, [], true);
    }

    /**
     * Create new progression entry
     * 
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $manager
     * @param UrlGeneratorInterface $urlgenerator
     * @param ValidatorInterface $validator
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[Route('/api/progression', name: 'progression.post', methods: ['POST'])]
    #[IsGranted("USER")]

    public function createProgression(Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, UrlGeneratorInterface $urlgenerator, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse
    {
        $progression = $serializer->deserialize($request->getContent(), Progression::class, "json");
        $dateNow = new DateTime();
        
        $progression->setStatus('on')
        ->setCreatedAt($dateNow)
        ->setUpdatedAt($dateNow);
        
        $errors = $validator->validate($progression);
        if($errors->count() > 0){
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $entityManager->persist($progression);
        $entityManager->flush();
        
        $cache->invalidateTags(["progressionCache"]);

        $jsonProgression = $serializer->serialize($progression, 'json',  ['groups' => "getAll"]);
        $location = $urlgenerator->generate("progression.get",  ["idProgression" => $progression->getId()], UrlGeneratorInterface::ABSOLUTE_PATH);
        return new JsonResponse($jsonProgression, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    /**
     * Update progression entry
     * 
     * @param Progression $progression
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $manager
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[Route('/api/progression/{id}', name: 'progression.put', methods: ['PUT'])]
    #[IsGranted("USER")]

    public function updateProgression(Progression $progression, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, TagAwareCacheInterface $cache): JsonResponse
    {
        $updatedProgression = $serializer->deserialize($request->getContent(), Progression::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $progression]);
        $updatedProgression->setUpdatedAt(new DateTime());
        $entityManager->persist($updatedProgression);
        $entityManager->flush();

        $cache->invalidateTags(["progressionCache"]);

       
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Delete progression entry
     * 
     * @param Progression $progression
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $manager
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[Route('/api/progression/{id}', name: 'progression.delete', methods: ['DELETE'])]
    #[IsGranted("USER")]

    public function deleteProgression(Progression $progression, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, TagAwareCacheInterface $cache): JsonResponse
    {
        $arrResponse = $request->toArray();

        $force = $arrResponse["force"];
        
        if($force){
            $entityManager->remove($progression);
        }else{
            $updatedProgression = $serializer->deserialize($request->getContent(), Progression::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $progression]);
            $updatedProgression->setStatus("off");
            $updatedProgression->setUpdatedAt(new DateTime());
            $entityManager->persist($updatedProgression);
        }
        
        $entityManager->flush();

        $cache->invalidateTags(["progressionCache"]);

        
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /** 
     * Renvoie toutes les entées progressions
     * 
     * @param Request $request
     * @param ProgressionRepository $repository
     * @param SerializerInterface $serializer
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[OA\Response(
        response:200,
        description: "Retourne la liste des progressions",
        content: new OA\JsonContent(
            type: "array",
            items: new OA\Items(ref: new Model(type:Progression::class))
        )
    )]
    #[Route('/api/progression', name: 'progression.getAll', methods: ['GET'])]
    public function getAllProgressions(Request $request, ProgressionRepository $repository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        $idCache = "getAllProgression";
        $cache->invalidateTags(["progressionCache"]);
        $jsonProgressions = $cache->get($idCache, function (ItemInterface $item) use ($repository, $serializer) {
            $item->tag("progressionCache");
            $progressions = $repository->findByStatusOn();
            return $serializer->serialize($progressions, 'json',  ['groups' => "getAll"]);
        });
        
        return new JsonResponse($jsonProgressions, 200, [], true);
    }


    /** 
     * Renvoie l'entée progression
     * 
     * @param Progression $progression
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
    #[Route('/api/progression/{idProgression}', name: 'progression.get', methods: ['GET'])]
    #[ParamConverter("progression", options: ["id" => "idProgression"])]
    public function getProgression(Progression $progression, SerializerInterface $serializer): JsonResponse
    {
       
        $jsonProgression = $serializer->serialize($progression, 'json', ['groups' => "getAll"]);

        return new JsonResponse($jsonProgression, 200, [], true);
    }



}
