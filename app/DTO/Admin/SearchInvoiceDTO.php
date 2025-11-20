<?php

declare(strict_types=1);

namespace App\DTO\Admin;

class SearchInvoiceDTO
{
    public function __construct(
        public ?string $type,
        public ?int $walletId,
        public ?int $categoryId,
        public ?string $status,
        public ?string $dateFrom,
        public ?string $dateTo,
    ) {}
}

