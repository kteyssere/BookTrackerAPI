<?php

namespace App\Controller;

use App\Entity\Review;
use App\Repository\ReviewRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\IsGranted;
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

class ReviewController extends AbstractController
{

    /**
     * Create new review entry
     * 
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $manager
     * @param UrlGeneratorInterface $urlgenerator
     * @param ValidatorInterface $validator 
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[Route('/api/review', name: 'review.post', methods: ['POST'])]
    public function createReview(Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, UrlGeneratorInterface $urlgenerator, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse
    {
        $review = $serializer->deserialize($request->getContent(), Review::class, "json");
        $dateNow = new DateTime();

        $review->setStatus('on')
        ->setCreatedAt($dateNow)
        ->setUpdatedAt($dateNow);


        $errors = $validator->validate($review);
        if($errors->count() > 0){
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $entityManager->persist($review);
        $entityManager->flush();
        
        $cache->invalidateTags(["reviewCache"]);

        $jsonReview = $serializer->serialize($review, 'json',  ['groups' => "getAll"]);
        $location = $urlgenerator->generate("review.get",  ["idReview" => $review->getId()], UrlGeneratorInterface::ABSOLUTE_PATH);
        return new JsonResponse($jsonReview, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    /**
     * Update review entry
     * 
     * @param Review $review
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $manager
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[Route('/api/review/{id}', name: 'review.put', methods: ['PUT'])]
    public function updateReview(Review $review, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, TagAwareCacheInterface $cache): JsonResponse
    {
        $updatedReview = $serializer->deserialize($request->getContent(), Review::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $review]);
        $updatedReview->setUpdatedAt(new DateTime());
        $entityManager->persist($updatedReview);
        $entityManager->flush();

        $cache->invalidateTags(["reviewCache"]);

       
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Delete review entry
     * 
     * @param Review $review
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $manager
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[Route('/api/review/{id}', name: 'review.delete', methods: ['DELETE'])]
    public function deleteReview(Review $review, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, TagAwareCacheInterface $cache): JsonResponse
    {
        $arrResponse = $request->toArray();

        $force = $arrResponse["force"];
        
        if($force){
            $entityManager->remove($review);
        }else{
            $updatedReview = $serializer->deserialize($request->getContent(), Review::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $review]);
            $updatedReview->setStatus("off");
            $updatedReview->setUpdatedAt(new DateTime());
            $entityManager->persist($updatedReview);
        }
        
        $entityManager->flush();

        $cache->invalidateTags(["reviewCache"]);

        
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /** 
     * Renvoie toutes les entées reviews
     * 
     * @param ReviewRepository $repository
     * @param SerializerInterface $serializer
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[OA\Response(
        response:200,
        description: "Retourne la liste des avis",
        content: new OA\JsonContent(
            type: "array",
            items: new OA\Items(ref: new Model(type:Review::class))
        )
    )]
    #[Route('/api/review', name: 'review.getAll', methods: ['GET'])]
    public function getAllReviews(ReviewRepository $repository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        $idCache = "getAllReview";
        $cache->invalidateTags(["reviewCache"]);
        $jsonReviews = $cache->get($idCache, function (ItemInterface $item) use ($repository, $serializer) {
            $item->tag("reviewCache");
            $reviews = $repository->findAll();
            return $serializer->serialize($reviews, 'json',  ['groups' => "getAll"]);
        });
        
        return new JsonResponse($jsonReviews, 200, [], true);
    }

    /** 
     * Renvoie l'entée review
     * 
     * @param Review $review
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
    #[Route('/api/review/{idReview}', name: 'review.get', methods: ['GET'])]
    #[ParamConverter("review", options: ["id" => "idReview"])]
    public function getReview(Review $review, SerializerInterface $serializer): JsonResponse
    {
        $jsonReview = $serializer->serialize($review, 'json', ['groups' => "getAll"]);

        return new JsonResponse($jsonReview, 200, [], true);
    }
}
