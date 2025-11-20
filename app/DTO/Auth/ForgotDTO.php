<?php

declare(strict_types=1);

namespace App\DTO\Auth;

class ForgotDTO
{  
    public function __construct(
        public string $email,
    ) {}
}