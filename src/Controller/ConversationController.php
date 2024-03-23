<?php

namespace App\Controller;

use App\Entity\Conversation;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ConversationRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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

class ConversationController extends AbstractController
{
    /**
     * Create new conversation entry
     * 
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $manager
     * @param UrlGeneratorInterface $urlgenerator
     * @param ValidatorInterface $validator
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[Route('/api/conversation', name: 'conversation.post', methods: ['POST'])]
    #[IsGranted("USER")]

    public function createConversation(Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, UrlGeneratorInterface $urlgenerator, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse
    {
        $conversation = $serializer->deserialize($request->getContent(), Conversation::class, "json");
        $dateNow = new DateTime();
        
        $conversation->setStatus('on')
        ->setCreatedAt($dateNow)
        ->setUpdatedAt($dateNow);

        $errors = $validator->validate($conversation);
        if($errors->count() > 0){
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $entityManager->persist($conversation);
        $entityManager->flush();
        
        $cache->invalidateTags(["conversationCache"]);

        $jsonConversation = $serializer->serialize($conversation, 'json',  ['groups' => "getAll"]);
        $location = $urlgenerator->generate("conversation.get",  ["idConversation" => $conversation->getId()], UrlGeneratorInterface::ABSOLUTE_PATH);
        return new JsonResponse($jsonConversation, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    /**
     * Update conversation entry
     * 
     * @param Conversation $conversation
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $manager
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[Route('/api/conversation/{id}', name: 'conversation.put', methods: ['PUT'])]
    #[IsGranted("USER")]

    public function updateConversation(Conversation $conversation, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, TagAwareCacheInterface $cache): JsonResponse
    {
        $updatedConversation = $serializer->deserialize($request->getContent(), Conversation::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $conversation]);
        $updatedConversation->setUpdatedAt(new DateTime());
        $entityManager->persist($updatedConversation);
        $entityManager->flush();

        $cache->invalidateTags(["conversationCache"]);

       
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Delete conversation entry
     * 
     * @param Conversation $conversation
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $manager
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[Route('/api/conversation/{id}', name: 'conversation.delete', methods: ['DELETE'])]
    #[IsGranted("USER")]

    public function deleteConversation(Conversation $conversation, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, TagAwareCacheInterface $cache): JsonResponse
    {
        $arrResponse = $request->toArray();

        $force = $arrResponse["force"];
        
        if($force){
            $entityManager->remove($conversation);
        }else{
            $updatedConversation = $serializer->deserialize($request->getContent(), Conversation::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $conversation]);
            $updatedConversation->setStatus("off");
            $updatedConversation->setUpdatedAt(new DateTime());
            $entityManager->persist($updatedConversation);
        }
        
        $entityManager->flush();

        $cache->invalidateTags(["conversationCache"]);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /** 
     * Renvoie toutes les entées conversations
     * 
     * @param ConversationRepository $repository
     * @param SerializerInterface $serializer
     * @param TagAwareCacheInterface $cache
     * @param Security $security
     * @return JsonResponse
     */
    #[OA\Response(
        response:200,
        description: "Retourne la liste des conversations",
        content: new OA\JsonContent(
            type: "array",
            items: new OA\Items(ref: new Model(type:Conversation::class))
        )
    )]
    #[Route('/api/conversation', name: 'conversation.getAll', methods: ['GET'])]
    public function getAllConversations(ConversationRepository $repository, SerializerInterface $serializer, TagAwareCacheInterface $cache, Security $security): JsonResponse
    {
        $idCache = "getAllConversation";
        $cache->invalidateTags(["conversationCache"]);
        $jsonConversations = $cache->get($idCache, function (ItemInterface $item) use ($repository, $serializer, $security) {
            $item->tag("conversationCache");
            $user = $security->getUser();
            $conversations = $repository->findAllOfUser($user);
            return $serializer->serialize($conversations, 'json',  ['groups' => "getAll"]);
        });
        
        
        return new JsonResponse($jsonConversations, 200, [], true);
    }

    /** 
     * Renvoie l'entée conversation
     * 
     * @param Conversation $conversation
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
    #[Route('/api/conversation/{idConversation}', name: 'conversation.get', methods: ['GET'])]
    #[ParamConverter("conversation", options: ["id" => "idConversation"])]
    public function getConversation(Conversation $conversation, SerializerInterface $serializer): JsonResponse
    {
        $jsonConversation = $serializer->serialize($conversation, 'json', ['groups' => "getAll"]);

        return new JsonResponse($jsonConversation, 200, [], true);
    }
}
