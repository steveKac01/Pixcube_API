<?php

namespace App\Controller;

use App\Dto\User\RegisterRequest;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RegisterController extends AbstractController
{
    #[Route('/api/register', name: 'app_register', methods: ['POST'])]
    public function index(
        #[MapRequestPayload] RegisterRequest $dto,
        UserRepository $userRepository,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em
    ): JsonResponse {

        if ($userRepository->findOneBy(['email' => $dto->email])) {
            return new JsonResponse(['message' => 'Email already used'], Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setEmail($dto->email)
            ->setPassword($hasher->hashPassword($user, $dto->password));

        $em->persist($user);
        $em->flush();
        
        return new JsonResponse([
            'email' => $user->getEmail()
        ], Response::HTTP_CREATED);
    }
}
