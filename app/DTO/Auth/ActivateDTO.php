<?php

declare(strict_types=1);

namespace App\DTO\Auth;

class ActivateDTO
{
    public function __construct(
        public string $token,
    ) {}
}