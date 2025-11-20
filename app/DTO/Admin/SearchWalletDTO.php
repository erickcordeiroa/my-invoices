<?php

declare(strict_types=1);

namespace App\DTO\Admin;

class SearchWalletDTO
{
    public function __construct(
        public string $name
    ) {}
}