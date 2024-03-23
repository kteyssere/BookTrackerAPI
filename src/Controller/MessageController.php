<?php

namespace App\Controller;

use App\Entity\Message;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\MessageRepository;
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

class MessageController extends AbstractController
{
    /**
     * Create new message entry
     * 
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $manager
     * @param UrlGeneratorInterface $urlgenerator
     * @param ValidatorInterface $validator
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[Route('/api/message', name: 'message.post', methods: ['POST'])]
    #[IsGranted("USER")]

    public function createMessage(Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, UrlGeneratorInterface $urlgenerator, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse
    {
        $message = $serializer->deserialize($request->getContent(), Message::class, "json");
        $dateNow = new DateTime();
        
        $message->setStatus('on')
        ->setCreatedAt($dateNow)
        ->setUpdatedAt($dateNow);

        $errors = $validator->validate($message);
        if($errors->count() > 0){
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $entityManager->persist($message);
        $entityManager->flush();
        
        $cache->invalidateTags(["messageCache"]);

        $jsonMessage = $serializer->serialize($message, 'json',  ['groups' => "getAll"]);
        $location = $urlgenerator->generate("message.get",  ["idMessage" => $message->getId()], UrlGeneratorInterface::ABSOLUTE_PATH);
        return new JsonResponse($jsonMessage, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    /**
     * Update message entry
     * 
     * @param Message $message
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $manager
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[Route('/api/message/{id}', name: 'message.put', methods: ['PUT'])]
    #[IsGranted("USER")]

    public function updateMessage(Message $message, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, TagAwareCacheInterface $cache): JsonResponse
    {
        $updatedMessage = $serializer->deserialize($request->getContent(), Message::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $message]);
        $updatedMessage->setUpdatedAt(new DateTime());
        $entityManager->persist($updatedMessage);
        $entityManager->flush();

        $cache->invalidateTags(["messageCache"]);

       
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Delete message entry
     * 
     * @param Message $message
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $manager
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[Route('/api/message/{id}', name: 'message.delete', methods: ['DELETE'])]
    #[IsGranted("USER")]

    public function deleteMessage(Message $message, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, TagAwareCacheInterface $cache): JsonResponse
    {
        $arrResponse = $request->toArray();

        $force = $arrResponse["force"];
        
        if($force){
            $entityManager->remove($message);
        }else{
            $updatedMessage = $serializer->deserialize($request->getContent(), Message::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $message]);
            $updatedMessage->setStatus("off");
            $updatedMessage->setUpdatedAt(new DateTime());
            $entityManager->persist($updatedMessage);
        }
        
        $entityManager->flush();

        $cache->invalidateTags(["messageCache"]);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /** 
     * Renvoie toutes les entées message
     * 
     * @param MessageRepository $repository
     * @param SerializerInterface $serializer
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[OA\Response(
        response:200,
        description: "Retourne la liste des messages",
        content: new OA\JsonContent(
            type: "array",
            items: new OA\Items(ref: new Model(type:Message::class))
        )
    )]
    #[Route('/api/message', name: 'message.getAll', methods: ['GET'])]
    public function getAllMessages(MessageRepository $repository, SerializerInterface $serializer, TagAwareCacheInterface $cache, Security $security): JsonResponse
    {
        $idCache = "getAllMessage";
        $cache->invalidateTags(["messageCache"]);
        $jsonMessages = $cache->get($idCache, function (ItemInterface $item) use ($repository, $serializer) {
            $item->tag("messageCache");
            $messages = $repository->findAll();
            return $serializer->serialize($messages, 'json',  ['groups' => "getAll"]);
        });
        
        
        return new JsonResponse($jsonMessages, 200, [], true);
    }

    /** 
     * Renvoie l'entée message
     * 
     * @param Message $message
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
    #[Route('/api/message/{idMessage}', name: 'message.get', methods: ['GET'])]
    #[ParamConverter("message", options: ["id" => "idMessage"])]
    public function getMessage(Message $message, SerializerInterface $serializer): JsonResponse
    {
        $jsonMessage = $serializer->serialize($message, 'json', ['groups' => "getAll"]);

        return new JsonResponse($jsonMessage, 200, [], true);
    }
}
