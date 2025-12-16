<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Lançamento criado com sucesso!';
    }

    protected function handleRecordCreation(array $data): Model
    {
        $invoice = parent::handleRecordCreation($data);

        // Se for parcelado, cria as demais parcelas
        if (isset($data['enrollments']) && $data['enrollments'] > 1 && $data['period'] !== 'single') {
            $this->createInstallments($invoice, $data);
        }

        return $invoice;
    }

    protected function createInstallments(Invoice $invoice, array $data): void
    {
        $totalInstallments = $data['enrollments'];
        $interval = $data['period'] === 'monthly' ? 'month' : 'year';

        for ($i = 2; $i <= $totalInstallments; $i++) {
            $dueDate = $invoice->due_at->copy()->add($interval, $i - 1);

            Invoice::create([
                'user_id' => $invoice->user_id,
                'wallet_id' => $invoice->wallet_id,
                'category_id' => $invoice->category_id,
                'invoice_of' => $invoice->id,
                'description' => $invoice->description,
                'type' => $invoice->type,
                'amount' => $invoice->amount,
                'currency' => $invoice->currency,
                'due_at' => $dueDate,
                'period' => $invoice->period,
                'enrollments' => $totalInstallments,
                'enrollments_of' => $i,
                'status' => 'unpaid',
            ]);
        }
    }
}

