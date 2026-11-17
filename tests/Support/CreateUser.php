<?php

namespace App\Tests\Support;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;


trait CreateUser
{

    private function createUser(string $email = 'user@test.fr'): User
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $user = (new User())->setEmail($email)
            ->setPassword('Test1234!');

        $em->persist($user);
        $em->flush();

        return $user;
    }

    private function createToken(User $user,)
    {
        $jwtManager = static::getContainer()->get(JWTTokenManagerInterface::class);

        return $jwtManager->create($user);
    }
}
