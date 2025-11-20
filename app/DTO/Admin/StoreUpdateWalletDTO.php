<?php

declare(strict_types=1);

namespace App\DTO\Admin;

class StoreUpdateWalletDTO
{
    public function __construct(
        public string $name,
        public int $balance = 0,
    ) {}
}