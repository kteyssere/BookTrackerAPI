<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Repository\CommentRepository;
use App\Entity\Picture;
use App\Repository\PictureRepository;
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
class CommentController extends AbstractController
{

    /**
     * Create new comment entry
     * 
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $manager
     * @param UrlGeneratorInterface $urlgenerator
     * @param ValidatorInterface $validator 
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[Route('/api/comment', name: 'comment.post', methods: ['POST'])]
    public function createComment(Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, UrlGeneratorInterface $urlgenerator, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse
    {
        $comment = $serializer->deserialize($request->getContent(), Comment::class, "json");
        $dateNow = new DateTime();
        
        $comment->setStatus('on')
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

        $comment->setCoverImage($picture);
        
        $errors = $validator->validate($comment);
        if($errors->count() > 1){
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $entityManager->persist($comment);
        $entityManager->flush();
        
        $cache->invalidateTags(["commentCache"]);

        $jsonComment = $serializer->serialize($comment, 'json',  ['groups' => "getAll"]);
        $location = $urlgenerator->generate("comment.get",  ["idComment" => $comment->getId()], UrlGeneratorInterface::ABSOLUTE_PATH);
        return new JsonResponse($jsonComment, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    /**
     * Update comment entry
     * 
     * @param Comment $comment
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $manager
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[Route('/api/comment/{id}', name: 'comment.put', methods: ['PUT'])]
    public function updateComment(Comment $comment, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, TagAwareCacheInterface $cache): JsonResponse
    {
        $updatedComment = $serializer->deserialize($request->getContent(), Comment::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $comment]);
        $updatedComment->setUpdatedAt(new DateTime());
        $entityManager->persist($updatedComment);
        $entityManager->flush();

        $cache->invalidateTags(["commentCache"]);

       
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Delete comment entry
     * 
     * @param Comment $comment
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $manager
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[Route('/api/comment/{id}', name: 'comment.delete', methods: ['DELETE'])]
    public function deleteComment(Comment $comment, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, TagAwareCacheInterface $cache): JsonResponse
    {
        $arrResponse = $request->toArray();

        $force = $arrResponse["force"];
        
        if($force){
            $entityManager->remove($comment);
        }else{
            $updatedComment = $serializer->deserialize($request->getContent(), Comment::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $comment]);
            $updatedComment->setStatus("off");
            $updatedComment->setUpdatedAt(new DateTime());
            $entityManager->persist($updatedComment);
        }
        
        $entityManager->flush();

        $cache->invalidateTags(["commentCache"]);

        
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /** 
     * Renvoie toutes les entées comments
     * 
     * @param CommentRepository $repository
     * @param SerializerInterface $serializer
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[OA\Response(
        response:200,
        description: "Retourne la liste des commentaires",
        content: new OA\JsonContent(
            type: "array",
            items: new OA\Items(ref: new Model(type:Comment::class))
        )
    )]
    #[Route('/api/comment', name: 'comment.getAll', methods: ['GET'])]
    public function getAllComments(CommentRepository $repository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        $idCache = "getAllComment";
        $cache->invalidateTags(["commentCache"]);
        $jsonComments = $cache->get($idCache, function (ItemInterface $item) use ($repository, $serializer) {
            $item->tag("commentCache");
            $comments = $repository->findAll();
            return $serializer->serialize($comments, 'json',  ['groups' => "getAll"]);
        });
        
        return new JsonResponse($jsonComments, 200, [], true);
    }

    /** 
     * Renvoie l'entée comment
     * 
     * @param Comment $comment
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
    #[Route('/api/comment/{idComment}', name: 'comment.get', methods: ['GET'])]
    #[ParamConverter("comment", options: ["id" => "idComment"])]
    public function getComment(Comment $comment, SerializerInterface $serializer): JsonResponse
    {
        $jsonComment = $serializer->serialize($comment, 'json', ['groups' => "getAll"]);

        return new JsonResponse($jsonComment, 200, [], true);
    }
}
