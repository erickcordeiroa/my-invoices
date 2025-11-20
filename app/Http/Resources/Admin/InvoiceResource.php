<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'wallet' => [
                'name' => $this->wallet->name ?? null,
            ],
            'category' => [
                'name' => $this->category->name ?? null,
                'type' => $this->category->type ?? null,
            ],
            'description' => $this->description,
            'type' => $this->type,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'due_at' => $this->due_at?->format('Y-m-d'),
            'paid_at' => $this->paid_at?->format('Y-m-d'),
            'repeat_when' => $this->repeat_when,
            'period' => $this->period,
            'enrollments' => $this->enrollments,
            'enrollments_of' => $this->enrollments_of,
            'invoice_of' => $this->invoice_of,
            'status' => $this->status,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}

