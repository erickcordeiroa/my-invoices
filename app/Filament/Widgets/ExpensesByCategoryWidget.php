<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Widgets\ChartWidget;

class ExpensesByCategoryWidget extends ChartWidget
{
    protected static ?string $heading = 'Despesas por Categoria';

    protected static ?string $description = 'Distribuição das despesas do mês';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 1;

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $userId = auth()->id();

        $expenses = Invoice::where('user_id', $userId)
            ->where('type', 'expense')
            ->where('status', 'paid')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->with('category')
            ->get()
            ->groupBy('category_id')
            ->map(fn ($items) => [
                'name' => $items->first()->category?->name ?? 'Sem categoria',
                'total' => $items->sum('amount') / 100,
            ])
            ->sortByDesc('total')
            ->take(6);

        $colors = [
            'rgba(244, 63, 94, 0.8)',
            'rgba(251, 146, 60, 0.8)',
            'rgba(250, 204, 21, 0.8)',
            'rgba(74, 222, 128, 0.8)',
            'rgba(56, 189, 248, 0.8)',
            'rgba(167, 139, 250, 0.8)',
        ];

        return [
            'datasets' => [
                [
                    'data' => $expenses->pluck('total')->toArray(),
                    'backgroundColor' => array_slice($colors, 0, $expenses->count()),
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $expenses->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
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
            'cutout' => '60%',
        ];
    }
}

