<?php

declare(strict_types=1);

namespace App\DTO\Admin;

class StoreUpdateInvoiceDTO
{
    public function __construct(
        public int $walletId,
        public int $categoryId,
        public ?string $description,
        public string $type,
        public int $amount = 0,
        public string $currency = 'BRL',
        public string $dueAt,
        public ?string $repeatWhen,
        public ?string $period = 'monthly',
        public ?int $enrollments,
    ) {}
}

