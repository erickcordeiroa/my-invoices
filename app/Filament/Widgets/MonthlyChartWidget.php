<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class MonthlyChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Receitas x Despesas';

    protected static ?string $description = 'Comparativo dos últimos 6 meses';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $userId = auth()->id();
        $months = collect();
        $incomes = collect();
        $expenses = collect();

        // Últimos 6 meses
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $months->push($date->translatedFormat('M/Y'));

            $monthlyIncome = Invoice::where('user_id', $userId)
                ->where('type', 'income')
                ->where('status', 'paid')
                ->whereMonth('paid_at', $date->month)
                ->whereYear('paid_at', $date->year)
                ->sum('amount');

            $monthlyExpense = Invoice::where('user_id', $userId)
                ->where('type', 'expense')
                ->where('status', 'paid')
                ->whereMonth('paid_at', $date->month)
                ->whereYear('paid_at', $date->year)
                ->sum('amount');

            $incomes->push($monthlyIncome / 100);
            $expenses->push($monthlyExpense / 100);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Receitas',
                    'data' => $incomes->toArray(),
                    'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Despesas',
                    'data' => $expenses->toArray(),
                    'backgroundColor' => 'rgba(244, 63, 94, 0.2)',
                    'borderColor' => 'rgb(244, 63, 94)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $months->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => "function(value) { return 'R$ ' + value.toLocaleString('pt-BR'); }",
                    ],
                ],
            ],
        ];
    }
}

