<?php

namespace App\Filament\Pages;

use App\Models\Category;
use App\Models\Invoice;
use App\Models\Wallet;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Collection;

class CashFlowReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.pages.cash-flow-report';

    protected static ?string $navigationGroup = 'Relatórios';

    protected static ?string $title = 'Fluxo de Caixa';

    protected static ?string $navigationLabel = 'Fluxo de Caixa';

    protected static ?int $navigationSort = 10;

    public ?array $data = [];

    // Dados do relatório
    public Collection $invoices;
    public Collection $categorySummary;
    public array $walletsSummary = [];
    public array $periodSummary = [];

    public function mount(): void
    {
        $this->form->fill([
            'date_start' => Carbon::now()->startOfMonth()->format('Y-m-d'),
            'date_end' => Carbon::now()->endOfMonth()->format('Y-m-d'),
            'category_id' => null,
            'wallet_id' => null,
            'status' => null,
            'type' => null,
            'only_installments' => false,
        ]);

        $this->invoices = collect();
        $this->categorySummary = collect();
        $this->generateReport();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filtros do Relatório')
                    ->description('Configure os filtros para gerar o relatório de fluxo de caixa')
                    ->icon('heroicon-o-funnel')
                    ->schema([
                        DatePicker::make('date_start')
                            ->label('Data Inicial')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->closeOnDateSelection()
                            ->default(Carbon::now()->startOfMonth()),

                        DatePicker::make('date_end')
                            ->label('Data Final')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->closeOnDateSelection()
                            ->default(Carbon::now()->endOfMonth()),

                        Select::make('type')
                            ->label('Tipo')
                            ->options([
                                'income' => 'Receitas',
                                'expense' => 'Despesas',
                            ])
                            ->placeholder('Todos')
                            ->native(false),

                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'paid' => 'Pago',
                                'unpaid' => 'Não Pago',
                                'overdue' => 'Atrasado',
                            ])
                            ->placeholder('Todos')
                            ->native(false),

                        Select::make('category_id')
                            ->label('Categoria')
                            ->options(fn () => Category::where('user_id', auth()->id())->pluck('name', 'id'))
                            ->placeholder('Todas')
                            ->searchable()
                            ->native(false),

                        Select::make('wallet_id')
                            ->label('Carteira')
                            ->options(fn () => Wallet::where('user_id', auth()->id())->pluck('name', 'id'))
                            ->placeholder('Todas')
                            ->searchable()
                            ->native(false),

                        Toggle::make('only_installments')
                            ->label('Apenas Parcelados')
                            ->helperText('Mostrar apenas lançamentos parcelados')
                            ->default(false),
                    ])
                    ->columns(4)
                    ->collapsible(),
            ])
            ->statePath('data');
    }

    public function generateReport(): void
    {
        $data = $this->form->getState();

        // Query base
        $query = Invoice::query()
            ->where('user_id', auth()->id())
            ->with(['category', 'wallet'])
            ->whereBetween('due_at', [$data['date_start'], $data['date_end']]);

        // Aplicar filtros
        if (!empty($data['type'])) {
            $query->where('type', $data['type']);
        }

        if (!empty($data['status'])) {
            $query->where('status', $data['status']);
        }

        if (!empty($data['category_id'])) {
            $query->where('category_id', $data['category_id']);
        }

        if (!empty($data['wallet_id'])) {
            $query->where('wallet_id', $data['wallet_id']);
        }

        if (!empty($data['only_installments'])) {
            $query->whereNotNull('enrollments')->where('enrollments', '>', 1);
        }

        $this->invoices = $query->orderBy('due_at')->get();

        // Gerar resumo por categoria
        $this->generateCategorySummary();

        // Gerar resumo das carteiras
        $this->generateWalletsSummary();

        // Gerar resumo do período
        $this->generatePeriodSummary();
    }

    protected function generateCategorySummary(): void
    {
        $this->categorySummary = $this->invoices
            ->groupBy('category_id')
            ->map(function ($items, $categoryId) {
                $category = $items->first()->category;
                $income = $items->where('type', 'income')->sum('amount');
                $expense = $items->where('type', 'expense')->sum('amount');
                $paid = $items->where('status', 'paid')->sum('amount');
                $unpaid = $items->where('status', '!=', 'paid')->sum('amount');

                return [
                    'category_id' => $categoryId,
                    'category_name' => $category?->name ?? 'Sem Categoria',
                    'category_type' => $category?->type ?? 'expense',
                    'total_income' => $income,
                    'total_expense' => $expense,
                    'total_paid' => $paid,
                    'total_unpaid' => $unpaid,
                    'balance' => $income - $expense,
                    'count' => $items->count(),
                ];
            })
            ->sortBy('category_name')
            ->values();
    }

    protected function generateWalletsSummary(): void
    {
        $wallets = Wallet::where('user_id', auth()->id())->get();

        $this->walletsSummary = $wallets->map(function ($wallet) {
            $walletInvoices = $this->invoices->where('wallet_id', $wallet->id);

            return [
                'wallet_id' => $wallet->id,
                'wallet_name' => $wallet->name,
                'current_balance' => $wallet->balance,
                'income_in_period' => $walletInvoices->where('type', 'income')->sum('amount'),
                'expense_in_period' => $walletInvoices->where('type', 'expense')->sum('amount'),
                'pending_income' => $walletInvoices->where('type', 'income')->where('status', '!=', 'paid')->sum('amount'),
                'pending_expense' => $walletInvoices->where('type', 'expense')->where('status', '!=', 'paid')->sum('amount'),
            ];
        })->toArray();
    }

    protected function generatePeriodSummary(): void
    {
        // Saldo atual total das carteiras
        $totalWalletBalance = Wallet::where('user_id', auth()->id())->sum('balance');

        // Total de receitas no período
        $totalIncome = $this->invoices->where('type', 'income')->sum('amount');
        $paidIncome = $this->invoices->where('type', 'income')->where('status', 'paid')->sum('amount');
        $pendingIncome = $this->invoices->where('type', 'income')->where('status', '!=', 'paid')->sum('amount');

        // Total de despesas no período
        $totalExpense = $this->invoices->where('type', 'expense')->sum('amount');
        $paidExpense = $this->invoices->where('type', 'expense')->where('status', 'paid')->sum('amount');
        $pendingExpense = $this->invoices->where('type', 'expense')->where('status', '!=', 'paid')->sum('amount');

        // Contas atrasadas
        $overdueExpense = $this->invoices
            ->where('type', 'expense')
            ->where('status', 'overdue')
            ->sum('amount');
        $overdueIncome = $this->invoices
            ->where('type', 'income')
            ->where('status', 'overdue')
            ->sum('amount');

        // Saldo projetado = Saldo Atual + Receitas Pendentes - Despesas Pendentes
        $projectedBalance = $totalWalletBalance + $pendingIncome - $pendingExpense;

        // Balanço do período
        $periodBalance = $totalIncome - $totalExpense;

        $this->periodSummary = [
            'total_wallet_balance' => $totalWalletBalance,
            'total_income' => $totalIncome,
            'paid_income' => $paidIncome,
            'pending_income' => $pendingIncome,
            'total_expense' => $totalExpense,
            'paid_expense' => $paidExpense,
            'pending_expense' => $pendingExpense,
            'overdue_expense' => $overdueExpense,
            'overdue_income' => $overdueIncome,
            'projected_balance' => $projectedBalance,
            'period_balance' => $periodBalance,
            'total_count' => $this->invoices->count(),
            'paid_count' => $this->invoices->where('status', 'paid')->count(),
            'unpaid_count' => $this->invoices->where('status', '!=', 'paid')->count(),
        ];
    }

    public function formatMoney(int $value): string
    {
        return 'R$ ' . number_format($value / 100, 2, ',', '.');
    }

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }

    public static function getNavigationBadge(): ?string
    {
        return null;
    }
}
