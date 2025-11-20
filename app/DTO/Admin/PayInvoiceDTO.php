<?php

declare(strict_types=1);

namespace App\DTO\Admin;

class PayInvoiceDTO
{
    public function __construct(
        public ?string $paidAt,
    ) {}
}

