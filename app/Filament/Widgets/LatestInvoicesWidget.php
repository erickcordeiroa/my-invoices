<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestInvoicesWidget extends BaseWidget
{
    protected static ?string $heading = 'Próximos Vencimentos';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Invoice::query()
                    ->where('user_id', auth()->id())
                    ->whereIn('status', ['unpaid', 'overdue'])
                    ->orderBy('due_at', 'asc')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('description')
                    ->label('Descrição')
                    ->limit(20)
                    ->tooltip(fn (Invoice $record): string => $record->description),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Valor')
                    ->formatStateUsing(fn (int $state, Invoice $record): string => 
                        ($record->type === 'expense' ? '- ' : '+ ') . 
                        'R$ ' . number_format($state / 100, 2, ',', '.')
                    )
                    ->color(fn (Invoice $record): string => $record->type === 'income' ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('due_at')
                    ->label('Vencimento')
                    ->date('d/m')
                    ->badge()
                    ->color(fn (Invoice $record): string => 
                        $record->due_at < now() ? 'danger' : 
                        ($record->due_at <= now()->addDays(3) ? 'warning' : 'gray')
                    ),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'unpaid' => 'Pendente',
                        'overdue' => 'Atrasado',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'unpaid' => 'warning',
                        'overdue' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->paginated(false)
            ->actions([
                Tables\Actions\Action::make('pay')
                    ->label('Pagar')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->size('sm')
                    ->action(function (Invoice $record): void {
                        $record->update([
                            'status' => 'paid',
                            'paid_at' => now(),
                        ]);

                        $wallet = $record->wallet;
                        if ($record->type === 'income') {
                            $wallet->balance += $record->amount;
                        } else {
                            $wallet->balance -= $record->amount;
                        }
                        $wallet->save();
                    }),
            ]);
    }
}

