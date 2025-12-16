<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\Wallet;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $userId = auth()->id();

        // Saldo total das carteiras
        $totalBalance = Wallet::where('user_id', $userId)->sum('balance');

        // Receitas do mês atual
        $monthlyIncome = Invoice::where('user_id', $userId)
            ->where('type', 'income')
            ->where('status', 'paid')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('amount');

        // Despesas do mês atual
        $monthlyExpense = Invoice::where('user_id', $userId)
            ->where('type', 'expense')
            ->where('status', 'paid')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('amount');

        // Contas pendentes
        $pendingCount = Invoice::where('user_id', $userId)
            ->where('status', 'unpaid')
            ->count();

        // Contas atrasadas
        $overdueCount = Invoice::where('user_id', $userId)
            ->where('status', 'overdue')
            ->count();

        // Valor total pendente
        $pendingAmount = Invoice::where('user_id', $userId)
            ->whereIn('status', ['unpaid', 'overdue'])
            ->sum('amount');

        // Cálculo de tendência (comparação com mês anterior)
        $lastMonthIncome = Invoice::where('user_id', $userId)
            ->where('type', 'income')
            ->where('status', 'paid')
            ->whereMonth('paid_at', now()->subMonth()->month)
            ->whereYear('paid_at', now()->subMonth()->year)
            ->sum('amount');

        $lastMonthExpense = Invoice::where('user_id', $userId)
            ->where('type', 'expense')
            ->where('status', 'paid')
            ->whereMonth('paid_at', now()->subMonth()->month)
            ->whereYear('paid_at', now()->subMonth()->year)
            ->sum('amount');

        $incomeChange = $lastMonthIncome > 0 
            ? round((($monthlyIncome - $lastMonthIncome) / $lastMonthIncome) * 100, 1) 
            : 0;

        $expenseChange = $lastMonthExpense > 0 
            ? round((($monthlyExpense - $lastMonthExpense) / $lastMonthExpense) * 100, 1) 
            : 0;

        return [
            Stat::make('Saldo Total', 'R$ ' . number_format($totalBalance / 100, 2, ',', '.'))
                ->description('Soma de todas as carteiras')
                ->descriptionIcon('heroicon-m-wallet')
                ->color($totalBalance >= 0 ? 'success' : 'danger')
                ->chart([7, 2, 10, 3, 15, 4, 17]),

            Stat::make('Receitas do Mês', 'R$ ' . number_format($monthlyIncome / 100, 2, ',', '.'))
                ->description($incomeChange >= 0 ? "+{$incomeChange}% vs mês anterior" : "{$incomeChange}% vs mês anterior")
                ->descriptionIcon($incomeChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color('success')
                ->chart([3, 5, 2, 8, 4, 6, $monthlyIncome > 0 ? 10 : 1]),

            Stat::make('Despesas do Mês', 'R$ ' . number_format($monthlyExpense / 100, 2, ',', '.'))
                ->description($expenseChange <= 0 ? "{$expenseChange}% vs mês anterior" : "+{$expenseChange}% vs mês anterior")
                ->descriptionIcon($expenseChange <= 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-arrow-trending-up')
                ->color('danger')
                ->chart([8, 6, 9, 5, 7, 4, $monthlyExpense > 0 ? 10 : 1]),

            Stat::make('Contas Pendentes', $pendingCount)
                ->description('R$ ' . number_format($pendingAmount / 100, 2, ',', '.') . ' a pagar')
                ->descriptionIcon('heroicon-m-clock')
                ->color($overdueCount > 0 ? 'danger' : ($pendingCount > 0 ? 'warning' : 'success')),
        ];
    }
}

