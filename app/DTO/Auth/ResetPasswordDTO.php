<?php

declare(strict_types=1);

namespace App\DTO\Auth;

class ResetPasswordDTO
{  
    public function __construct(
        public string $email,
        public string $password,
        public string $token,
    ) {}
}