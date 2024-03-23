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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class PersonaController extends AbstractController
{
     /**
     * Create new persona entry
     * 
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $manager
     * @param UrlGeneratorInterface $urlgenerator
     * @param ValidatorInterface $validator 
     * @param TagAwareCacheInterface $cache 
     * @param UserPasswordHasherInterface $passwordHasher
     * @return JsonResponse
     */
    #[Route('/api/register', name: 'persona.post', methods: ['POST'])]
    public function createPersona(Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, UrlGeneratorInterface $urlgenerator, ValidatorInterface $validator, TagAwareCacheInterface $cache, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $persona = $serializer->deserialize($request->getContent(), Persona::class, "json");
        $user = $serializer->deserialize($request->getContent(), User::class, "json");
        $dateNow = new DateTime();
      
        $persona->setStatus('on')
        ->setAnonymous(false)
        ->setCreatedAt($dateNow)
        ->setUpdatedAt($dateNow);

        $errors = $validator->validate($persona);
        if($errors->count() > 0){
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
        
        $entityManager->persist($persona);

        $arrResponse = $request->toArray();
        $plaintextPassword = $arrResponse["password"];
        
        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $plaintextPassword
        );
        
        $user->setUsername($arrResponse["username"])
        ->setRoles(["USER"])
        ->setPassword($hashedPassword)
        ->setPersona($persona);

        $errors = $validator->validate($user);
        if($errors->count() > 0){
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $entityManager->persist($user);

        $entityManager->flush();
        
        $cache->invalidateTags(["personaCache"]);

        $jsonPersona = $serializer->serialize($persona, 'json',  ['groups' => "getAll"]);
        $location = $urlgenerator->generate("persona.get",  ["idPersona" => $persona->getId()], UrlGeneratorInterface::ABSOLUTE_PATH);
        return new JsonResponse($jsonPersona, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    /**
     * Update persona entry
     * 
     * @param Persona $persona
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $manager
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[Route('/api/persona/{id}', name: 'persona.put', methods: ['PUT'])]
    public function updatePersona(Persona $persona, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, TagAwareCacheInterface $cache): JsonResponse
    {
        $updatedPersona = $serializer->deserialize($request->getContent(), Persona::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $persona]);
        $updatedPersona->setUpdatedAt(new DateTime());
        $entityManager->persist($updatedPersona);
        $entityManager->flush();

        $cache->invalidateTags(["personaCache"]);

       
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Delete persona entry
     * 
     * @param Persona $persona
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $manager
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[Route('/api/persona/{id}', name: 'persona.delete', methods: ['DELETE'])]
    #[IsGranted("ADMIN")]

    public function deletePersona(Persona $persona, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, TagAwareCacheInterface $cache): JsonResponse
    {
        $arrResponse = $request->toArray();

        $force = $arrResponse["force"];
        
        if($force){
            $entityManager->remove($persona);
        }else{
            $updatedPersona = $serializer->deserialize($request->getContent(), Persona::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $persona]);
            $updatedPersona->setStatus("off");
            $updatedPersona->setUpdatedAt(new DateTime());
            $entityManager->persist($updatedPersona);
        }
        
        $entityManager->flush();

        $cache->invalidateTags(["personaCache"]);

        
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /** 
     * Renvoie toutes les entées personas
     * 
     * @param PersonaRepository $repository
     * @param SerializerInterface $serializer
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[OA\Response(
        response:200,
        description: "Retourne la liste des profils",
        content: new OA\JsonContent(
            type: "array",
            items: new OA\Items(ref: new Model(type:Persona::class))
        )
    )]
    #[Route('/api/persona', name: 'persona.getAll', methods: ['GET'])]
    #[IsGranted("ADMIN")]
    public function getAllPersonas(PersonaRepository $repository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        $idCache = "getAllPersona";
        $cache->invalidateTags(["personaCache"]);
        $jsonPersonas = $cache->get($idCache, function (ItemInterface $item) use ($repository, $serializer) {
            $item->tag("personaCache");
            $personas = $repository->findAll();
            return $serializer->serialize($personas, 'json',  ['groups' => "getAll"]);
        });
        
        return new JsonResponse($jsonPersonas, 200, [], true);
    }

    /** 
     * Renvoie l'entée persona
     * 
     * @param Persona $persona
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
    #[Route('/api/persona/{idPersona}', name: 'persona.get', methods: ['GET'])]
    #[ParamConverter("persona", options: ["id" => "idPersona"])]
    public function getPersona(Persona $persona, SerializerInterface $serializer): JsonResponse
    {
        $jsonPersona = $serializer->serialize($persona, 'json', ['groups' => "getAll"]);

        return new JsonResponse($jsonPersona, 200, [], true);
    }
}
