<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Novo Lançamento')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        $userId = auth()->id();

        return [
            'todos' => Tab::make('Todos')
                ->badge(Invoice::query()->where('user_id', $userId)->count())
                ->badgeColor('gray'),

            'receitas' => Tab::make('Receitas')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'income'))
                ->badge(Invoice::query()->where('user_id', $userId)->where('type', 'income')->count())
                ->badgeColor('success')
                ->icon('heroicon-o-arrow-trending-up'),

            'despesas' => Tab::make('Despesas')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'expense'))
                ->badge(Invoice::query()->where('user_id', $userId)->where('type', 'expense')->count())
                ->badgeColor('danger')
                ->icon('heroicon-o-arrow-trending-down'),

            'pagos' => Tab::make('Pagos')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'paid'))
                ->badge(Invoice::query()->where('user_id', $userId)->where('status', 'paid')->count())
                ->badgeColor('success')
                ->icon('heroicon-o-check-circle'),

            'pendentes' => Tab::make('Pendentes')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'unpaid'))
                ->badge(Invoice::query()->where('user_id', $userId)->where('status', 'unpaid')->count())
                ->badgeColor('warning')
                ->icon('heroicon-o-clock'),

            'atrasados' => Tab::make('Atrasados')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'overdue'))
                ->badge(Invoice::query()->where('user_id', $userId)->where('status', 'overdue')->count())
                ->badgeColor('danger')
                ->icon('heroicon-o-exclamation-circle'),
        ];
    }
}

