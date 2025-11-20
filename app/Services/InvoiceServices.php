<?php

namespace App\Services;

use App\DTO\Admin\PayInvoiceDTO;
use App\DTO\Admin\SearchInvoiceDTO;
use App\DTO\Admin\StoreUpdateInvoiceDTO;
use App\Exceptions\Admin\InvoiceException;
use App\Models\Category;
use App\Models\Invoice;
use App\Models\Wallet;
use App\Services\WalletServices;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvoiceServices
{
    public function __construct(
        private readonly WalletServices $walletServices
    ) {}
    public function getAll(): CursorPaginator
    {
        $user = Auth::user();
        $invoices = Invoice::where('user_id', $user->id)
            ->with(['wallet', 'category'])
            ->orderBy('due_at', 'desc')
            ->cursorPaginate(12);

        return $invoices;
    }

    public function search(SearchInvoiceDTO $data): Collection
    {
        $user = Auth::user();
        $query = Invoice::where('user_id', $user->id)
            ->with(['wallet', 'category']);

        if ($data->type) {
            $query->where('type', $data->type);
        }

        if ($data->walletId) {
            $query->where('wallet_id', $data->walletId);
        }

        if ($data->categoryId) {
            $query->where('category_id', $data->categoryId);
        }

        if ($data->status) {
            $query->where('status', $data->status);
        }

        if ($data->dateFrom) {
            $query->where('due_at', '>=', $data->dateFrom);
        }

        if ($data->dateTo) {
            $query->where('due_at', '<=', $data->dateTo);
        }

        $invoices = $query->orderBy('due_at', 'desc')->get();

        if ($invoices->isEmpty()) {
            throw new InvoiceException('Nenhuma invoice encontrada');
        }

        return $invoices;
    }

    public function show(int $id): Invoice
    {
        $user = Auth::user();
        $invoice = Invoice::where('user_id', $user->id)
            ->where('id', $id)
            ->with(['wallet', 'category'])
            ->first();

        if (!$invoice || $invoice->user_id != Auth::user()->id) {
            throw new InvoiceException('Invoice não encontrada');
        }

        return $invoice;
    }

    public function store(StoreUpdateInvoiceDTO $data): Invoice|Collection
    {
        $user = Auth::user();

        // Validar se carteira pertence ao usuário
        $wallet = Wallet::where('id', $data->walletId)
            ->where('user_id', $user->id)
            ->first();

        if (!$wallet) {
            throw new InvoiceException('Carteira não encontrada');
        }

        // Validar se categoria pertence ao usuário
        $category = Category::where('id', $data->categoryId)
            ->where('user_id', $user->id)
            ->first();

        if (!$category) {
            throw new InvoiceException('Categoria não encontrada');
        }

        if (!$data->enrollments && !$data->repeatWhen) {
            return $this->createSingleInvoice($user, $data);
        }   

        if ($data->enrollments && $data->enrollments > 1 && !$data->repeatWhen) {
            return $this->createInstallmentInvoices($user, $data);
        }

        if ($data->repeatWhen === 'monthly' && $data->enrollments) {    
            return $this->createRecurringInvoice($user, $data);
        }

        throw new InvoiceException('Configuração de invoice inválida');
    }

    private function createSingleInvoice($user, StoreUpdateInvoiceDTO $data): Invoice
    {
        $invoice = $user->invoices()->create([
            'wallet_id' => $data->walletId,
            'category_id' => $data->categoryId,
            'description' => $data->description,
            'type' => $data->type,
            'amount' => $data->amount,
            'currency' => $data->currency ?? 'BRL',
            'due_at' => $data->dueAt,
            'repeat_when' => null,
            'period' => "unique",
            'enrollments' => null,
            'enrollments_of' => null,
            'invoice_of' => null,
            'status' => $this->calculateStatus($data->dueAt),
        ]);

        return $invoice->load(['wallet', 'category']);
    }

    private function createInstallmentInvoices($user, StoreUpdateInvoiceDTO $data): Invoice|Collection
    {
        $invoices = new Collection();
        $firstInvoice = null;
        $dueDate = Carbon::parse($data->dueAt);
        
        // Garantir que enrollments é um inteiro
        $enrollments = (int) $data->enrollments;

        DB::beginTransaction();
        try {
            for ($i = 1; $i <= $enrollments; $i++) {
                $invoiceData = [
                    'wallet_id' => $data->walletId,
                    'category_id' => $data->categoryId,
                    'description' => $data->description . ' (' . $i . '/' . $enrollments . ')',
                    'type' => $data->type,
                    'amount' => $data->amount,
                    'currency' => $data->currency ?? 'BRL',
                    'due_at' => $dueDate->copy()->format('Y-m-d'),
                    'repeat_when' => null,
                    'period' => $data->period ?? 'monthly',
                    'enrollments' => $enrollments,
                    'enrollments_of' => $i,
                    'invoice_of' => $firstInvoice?->id,
                    'status' => $this->calculateStatus($dueDate->format('Y-m-d')),
                ];
                
                $invoice = $user->invoices()->create($invoiceData);

                if ($i === 1) {
                    $firstInvoice = $invoice;
                } else {
                    $invoice->update(['invoice_of' => $firstInvoice->id]);
                }

                $invoices->push($invoice);

                if ($data->period === 'monthly') {
                    $dueDate->addMonth();
                }
            }

            DB::commit();
            return $invoices;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new InvoiceException('Erro ao criar invoices parceladas: ' . $e->getMessage());
        }
    }

    //TODO: validar se esse metodo é necessário ou não.
    private function createRecurringInvoice($user, StoreUpdateInvoiceDTO $data): Invoice
    {
        // Garantir que enrollments é um inteiro
        $enrollments = (int) $data->enrollments;
        
        $invoice = $user->invoices()->create([
            'wallet_id' => $data->walletId,
            'category_id' => $data->categoryId,
            'description' => $data->description,
            'type' => $data->type,
            'amount' => $data->amount,
            'currency' => $data->currency ?? 'BRL',
            'due_at' => $data->dueAt,
            'repeat_when' => $data->repeatWhen,
            'period' => $data->period ?? 'monthly',
            'enrollments' => $enrollments,
            'enrollments_of' => 1,
            'invoice_of' => null,
            'status' => $this->calculateStatus($data->dueAt),
        ]);

        return $invoice->load(['wallet', 'category']);
    }

    public function update(int $id, StoreUpdateInvoiceDTO $data): Invoice
    {
        $user = Auth::user();
        $invoice = Invoice::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$invoice || $invoice->user_id != Auth::user()->id) {
            throw new InvoiceException('Invoice não encontrada');
        }

        // Não permitir atualização de parcelas individuais ou recorrentes já processadas
        if ($invoice->invoice_of) {
            throw new InvoiceException('Não é possível atualizar uma parcela individual. Atualize a invoice principal.');
        }

        if ($invoice->repeat_when && $invoice->enrollments_of > 1) {
            throw new InvoiceException('Não é possível atualizar invoice recorrente já processada');
        }

        // Validar carteira e categoria
        $wallet = Wallet::where('id', $data->walletId)
            ->where('user_id', $user->id)
            ->first();

        if (!$wallet) {
            throw new InvoiceException('Carteira não encontrada ou não pertence ao usuário');
        }

        $category = Category::where('id', $data->categoryId)
            ->where('user_id', $user->id)
            ->first();

        if (!$category) {
            throw new InvoiceException('Categoria não encontrada ou não pertence ao usuário');
        }

        if ($category->type !== $data->type) {
            throw new InvoiceException('Tipo da categoria não corresponde ao tipo da invoice');
        }

        DB::beginTransaction();
        try {
            $paidAt = $invoice->paid_at ? ($invoice->paid_at instanceof \DateTime ? $invoice->paid_at->format('Y-m-d') : $invoice->paid_at) : null;
            
            // Atualizar invoice principal
            $invoice->update([
                'wallet_id' => $data->walletId,
                'category_id' => $data->categoryId,
                'description' => $data->description,
                'type' => $data->type,
                'amount' => $data->amount,
                'currency' => $data->currency ?? 'BRL',
                'due_at' => $data->dueAt,
                'status' => $this->calculateStatus($data->dueAt, $paidAt),
            ]);

            // Se a invoice tiver parcelas, atualizar todas elas
            $installments = Invoice::where('invoice_of', $invoice->id)
                ->where('user_id', $user->id)
                ->orderBy('enrollments_of', 'asc')
                ->get();

            if ($installments->isNotEmpty()) {
                $enrollments = $invoice->enrollments ?? $installments->count() + 1;
                $period = $invoice->period ?? 'monthly';

                // Atualizar descrição da invoice principal com número da parcela
                $invoice->update([
                    'description' => $data->description . ' (1/' . $enrollments . ')',
                ]);

                // Atualizar cada parcela
                foreach ($installments as $installment) {
                    $installmentNumber = $installment->enrollments_of;
                    
                    // Calcular nova data de vencimento baseada no período
                    $installmentDueDate = new \DateTime($data->dueAt);
                    if ($installmentNumber > 1 && $period === 'monthly') {
                        $installmentDueDate->modify('+' . ($installmentNumber - 1) . ' months');
                    }

                    $installmentPaidAt = $installment->paid_at 
                        ? ($installment->paid_at instanceof \DateTime 
                            ? $installment->paid_at->format('Y-m-d') 
                            : $installment->paid_at) 
                        : null;

                    $installment->update([
                        'wallet_id' => $data->walletId,
                        'category_id' => $data->categoryId,
                        'description' => $data->description . ' (' . $installmentNumber . '/' . $enrollments . ')',
                        'type' => $data->type,
                        'amount' => $data->amount,
                        'currency' => $data->currency ?? 'BRL',
                        'due_at' => $installmentDueDate->format('Y-m-d'),
                        'status' => $this->calculateStatus($installmentDueDate->format('Y-m-d'), $installmentPaidAt),
                    ]);
                }
            }

            DB::commit();
            return $invoice->load(['wallet', 'category']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new InvoiceException('Erro ao atualizar invoice: ' . $e->getMessage());
        }
    }

    public function delete(int $id): void
    {
        $user = Auth::user();
        $invoice = Invoice::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$invoice) {
            throw new InvoiceException('Invoice não encontrada');
        }

        if ($invoice->invoice_of) {
            throw new InvoiceException('Não é possível deletar invoice parcelada. Delete a invoice principal.');
        }

        DB::beginTransaction();
        try {
            $installments = Invoice::where('invoice_of', $invoice->id)
                ->where('user_id', $user->id)
                ->get(); 

            if ($installments->isNotEmpty()) {
                foreach ($installments as $installment) {
                    if ($installment->paid_at) {
                        $this->walletServices->updateBalanceByInvoice($installment, 'unpay');
                    }
                    $installment->delete();
                }
            }

            if ($invoice->paid_at) {
                $this->walletServices->updateBalanceByInvoice($invoice, 'unpay');
            }

            $invoice->delete();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new InvoiceException('Erro ao deletar invoice: ' . $e->getMessage());
        }
    }

    public function pay(int $id, PayInvoiceDTO $data): Invoice
    {
        $user = Auth::user();
        $invoice = Invoice::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$invoice || $invoice->user_id != Auth::user()->id) {
            throw new InvoiceException('Fatura não encontrada');
        }

        if ($invoice->paid_at) {
            throw new InvoiceException('Fatura já está paga');
        }

        $paidAt = $data->paidAt ? Carbon::parse($data->paidAt) : Carbon::now();

        DB::beginTransaction();
        try {
            $invoice->update([
                'paid_at' => $paidAt->format('Y-m-d'),
                'status' => 'paid',
            ]);

            // Atualizar saldo da carteira (crédito ou débito)
            $this->walletServices->updateBalanceByInvoice($invoice, 'pay');

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new InvoiceException('Erro ao marcar fatura como paga: ' . $e->getMessage());
        }

        return $invoice->fresh()->load(['wallet', 'category']);
    }

    public function unpay(int $id): Invoice
    {
        $user = Auth::user();
        $invoice = Invoice::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$invoice || $invoice->user_id != Auth::user()->id) {
            throw new InvoiceException('Fatura não encontrada');
        }

        if (!$invoice->paid_at) {
            throw new InvoiceException('Fatura não está paga');
        }

        $dueAt = $invoice->due_at instanceof \DateTime 
            ? $invoice->due_at->format('Y-m-d') 
            : $invoice->due_at;
        
        DB::beginTransaction();
        try {
            $invoice->update([
                'paid_at' => null,
                'status' => $this->calculateStatus($dueAt),
            ]);

            // Reverter saldo da carteira (reverter crédito ou débito)
            $this->walletServices->updateBalanceByInvoice($invoice, 'unpay');

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new InvoiceException('Erro ao marcar fatura como não paga: ' . $e->getMessage());
        }

        return $invoice->fresh()->load(['wallet', 'category']);
    }

    private function calculateStatus(string $dueAt, ?string $paidAt = null): string
    {
        if ($paidAt) {
            return 'paid';
        }

        $dueDate = Carbon::parse($dueAt);
        $today = Carbon::today();

        if ($dueDate->isPast() && !$dueDate->isToday()) {
            return 'overdue';
        }

        return 'unpaid';
    }
}

