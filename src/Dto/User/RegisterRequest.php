<?php

namespace App\Dto\User;

use Symfony\Component\Validator\Constraints as Assert;

class RegisterRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 180)]
        public readonly string $email = '',

        #[Assert\NotBlank]
        #[Assert\Length(min: 8, max: 100)]
        #[Assert\Regex(
            pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).+$/',
            message: 'Le mot de passe doit contenir une minuscule, une majuscule, un chiffre et un caractère spécial.'
        )]
        public readonly string $password = '',
    ) {}
}
