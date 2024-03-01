<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

use App\Entity\Picture;
use App\Repository\PictureRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;


class PictureController extends AbstractController
{

    #[Route('/', name: 'app_picture')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/PictureController.php'
        ]);
    }


   /**
     * Create new picture entry
     * 
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $manager
     * @param UrlGeneratorInterface $urlgenerator
     * @return JsonResponse
     */
    #[Route('/api/picture', name: 'picture.post', methods: ['POST'])]
    public function createPicture(Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, UrlGeneratorInterface $urlgenerator): JsonResponse
    {
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
        $entityManager->flush();
        
        $jsonPicture = $serializer->serialize($picture, 'json');
        $location = $urlgenerator->generate('picture.get',  ['idPicture'=>$picture->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonPicture, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    /** 
     * Renvoie l'entée picture
     * 
     * @param int $idPicture
     * @param PictureRepository $repository
     * @param UrlGeneratorInterface $urlgenerator
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
    #[Route('/api/picture/{idPicture}', name: 'picture.get', methods: ['GET'])]
    public function getPicture(int $idPicture, PictureRepository $repository, UrlGeneratorInterface $urlgenerator, SerializerInterface $serializer): JsonResponse
    {
        $picture = $repository->find($idPicture);
        $location = $urlgenerator->generate('app_picture', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $location = $location . str_replace('/public/', "", $picture->getPublicPath())."/".$picture->getRealPath();

        return $picture ?
        new JsonResponse($serializer->serialize($picture,'json'), Response::HTTP_OK, ["Location" => $location],true) :
        new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }

}
