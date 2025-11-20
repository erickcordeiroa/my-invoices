<?php

declare(strict_types=1);

namespace App\DTO\Admin;

class StoreUpdateCategoryDTO
{
    public function __construct(
        public string $name,
        public string $type,
    ) {}
}