<?php

namespace App\Filament\Widgets;

use App\Models\Wallet;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class WalletsOverviewWidget extends BaseWidget
{
    protected static ?string $heading = 'Minhas Carteiras';

    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Wallet::query()
                    ->where('user_id', auth()->id())
                    ->orderBy('balance', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Carteira')
                    ->weight('bold')
                    ->icon('heroicon-o-wallet'),

                Tables\Columns\TextColumn::make('balance')
                    ->label('Saldo')
                    ->formatStateUsing(fn (int $state): string => 'R$ ' . number_format($state / 100, 2, ',', '.'))
                    ->color(fn (int $state): string => $state >= 0 ? 'success' : 'danger')
                    ->weight('bold'),
            ])
            ->paginated(false)
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->size('sm')
                    ->url(fn (Wallet $record): string => route('filament.admin.resources.wallets.edit', $record)),
            ]);
    }
}

