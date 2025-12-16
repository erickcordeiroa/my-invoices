<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Infolists\Components;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Editar'),
            Actions\DeleteAction::make()
                ->label('Excluir'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Informações do Lançamento')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('description')
                                    ->label('Descrição')
                                    ->weight('bold')
                                    ->size('lg'),

                                Components\TextEntry::make('type')
                                    ->label('Tipo')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'income' => 'Receita',
                                        'expense' => 'Despesa',
                                        default => $state,
                                    })
                                    ->color(fn (string $state): string => match ($state) {
                                        'income' => 'success',
                                        'expense' => 'danger',
                                        default => 'gray',
                                    }),

                                Components\TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'unpaid' => 'Não Pago',
                                        'paid' => 'Pago',
                                        'overdue' => 'Atrasado',
                                        default => $state,
                                    })
                                    ->color(fn (string $state): string => match ($state) {
                                        'unpaid' => 'warning',
                                        'paid' => 'success',
                                        'overdue' => 'danger',
                                        default => 'gray',
                                    }),
                            ]),
                    ]),

                Components\Section::make('Valores')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('amount')
                                    ->label('Valor')
                                    ->formatStateUsing(fn (int $state): string => 'R$ ' . number_format($state / 100, 2, ',', '.'))
                                    ->weight('bold')
                                    ->size('lg'),

                                Components\TextEntry::make('currency')
                                    ->label('Moeda'),

                                Components\TextEntry::make('wallet.name')
                                    ->label('Carteira')
                                    ->badge()
                                    ->color('info'),
                            ]),
                    ]),

                Components\Section::make('Categoria e Datas')
                    ->schema([
                        Components\Grid::make(4)
                            ->schema([
                                Components\TextEntry::make('category.name')
                                    ->label('Categoria')
                                    ->badge()
                                    ->color('gray'),

                                Components\TextEntry::make('due_at')
                                    ->label('Vencimento')
                                    ->date('d/m/Y'),

                                Components\TextEntry::make('paid_at')
                                    ->label('Pago em')
                                    ->date('d/m/Y')
                                    ->placeholder('Não pago'),

                                Components\TextEntry::make('period')
                                    ->label('Período')
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'single' => 'Único',
                                        'monthly' => 'Mensal',
                                        'yearly' => 'Anual',
                                        default => $state ?? '-',
                                    }),
                            ]),
                    ]),

                Components\Section::make('Informações Adicionais')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('created_at')
                                    ->label('Criado em')
                                    ->dateTime('d/m/Y H:i'),

                                Components\TextEntry::make('updated_at')
                                    ->label('Atualizado em')
                                    ->dateTime('d/m/Y H:i'),
                            ]),
                    ])
                    ->collapsed(),
            ]);
    }
}

