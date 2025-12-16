<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ExpensesByCategoryWidget;
use App\Filament\Widgets\LatestInvoicesWidget;
use App\Filament\Widgets\MonthlyChartWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Widgets\WalletsOverviewWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $title = 'Painel de Controle';

    protected static ?string $navigationLabel = 'Início';

    public function getColumns(): int|string|array
    {
        return 2;
    }

    public function getWidgets(): array
    {
        return [
            StatsOverviewWidget::class,
            MonthlyChartWidget::class,
            ExpensesByCategoryWidget::class,
            LatestInvoicesWidget::class,
            WalletsOverviewWidget::class,
        ];
    }
}

