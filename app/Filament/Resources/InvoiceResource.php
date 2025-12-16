<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Category;
use App\Models\Invoice;
use App\Models\Wallet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Financeiro';

    protected static ?string $modelLabel = 'Lançamento';

    protected static ?string $pluralModelLabel = 'Lançamentos';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\Section::make('Tipo de Lançamento')
                            ->description('Defina se é uma receita ou despesa')
                            ->icon('heroicon-o-arrows-right-left')
                            ->schema([
                                Forms\Components\ToggleButtons::make('type')
                                    ->label('Tipo')
                                    ->options([
                                        'income' => 'Receita',
                                        'expense' => 'Despesa',
                                    ])
                                    ->icons([
                                        'income' => 'heroicon-o-arrow-trending-up',
                                        'expense' => 'heroicon-o-arrow-trending-down',
                                    ])
                                    ->colors([
                                        'income' => 'success',
                                        'expense' => 'danger',
                                    ])
                                    ->required()
                                    ->inline()
                                    ->default('expense')
                                    ->live()
                                    ->afterStateUpdated(fn (Forms\Set $set) => $set('category_id', null))
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(['lg' => 2]),

                        Forms\Components\Section::make('Detalhes do Lançamento')
                            ->description('Informações sobre o lançamento')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Forms\Components\TextInput::make('description')
                                    ->label('Descrição')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Ex: Aluguel, Salário, Mercado...')
                                    ->autofocus()
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('amount')
                                    ->label('Valor')
                                    ->required()
                                    ->prefix('R$')
                                    ->mask(\Filament\Support\RawJs::make(<<<'JS'
                                        $money($input, ',', '.', 2)
                                    JS))
                                    ->stripCharacters('.')
                                    ->dehydrateStateUsing(function ($state) {
                                        if (empty($state)) return 0;
                                        // Remove pontos de milhar e substitui vírgula por ponto
                                        $value = str_replace('.', '', $state);
                                        $value = str_replace(',', '.', $value);
                                        return (int) round(floatval($value) * 100);
                                    })
                                    ->formatStateUsing(function ($state) {
                                        if (empty($state) || !is_numeric($state)) return '0,00';
                                        return number_format((int) $state / 100, 2, ',', '.');
                                    })
                                    ->placeholder('0,00')
                                    ->helperText('Informe o valor do lançamento')
                                    ->columnSpan(['lg' => 1]),

                                Forms\Components\Select::make('currency')
                                    ->label('Moeda')
                                    ->options([
                                        'BRL' => '🇧🇷 Real (BRL)',
                                        'USD' => '🇺🇸 Dólar (USD)',
                                        'EUR' => '🇪🇺 Euro (EUR)',
                                    ])
                                    ->default('BRL')
                                    ->required()
                                    ->columnSpan(['lg' => 1]),

                                Forms\Components\Select::make('wallet_id')
                                    ->label('Carteira')
                                    ->relationship(
                                        name: 'wallet',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn (Builder $query) => $query->where('user_id', auth()->id())
                                    )
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Nome da Carteira')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('balance')
                                            ->label('Saldo Inicial')
                                            ->prefix('R$')
                                            ->default('0,00')
                                            ->mask(\Filament\Support\RawJs::make(<<<'JS'
                                                $money($input, ',', '.', 2)
                                            JS))
                                            ->stripCharacters('.')
                                            ->dehydrateStateUsing(function ($state) {
                                                if (empty($state)) return 0;
                                                $value = str_replace('.', '', $state);
                                                $value = str_replace(',', '.', $value);
                                                return (int) round(floatval($value) * 100);
                                            }),
                                        Forms\Components\Hidden::make('user_id')
                                            ->default(fn () => auth()->id()),
                                    ])
                                    ->columnSpan(['lg' => 1]),

                                Forms\Components\Select::make('category_id')
                                    ->label('Categoria')
                                    ->options(function (Get $get) {
                                        $type = $get('type');
                                        return Category::query()
                                            ->where('user_id', auth()->id())
                                            ->when($type, fn ($query) => $query->where('type', $type))
                                            ->pluck('name', 'id');
                                    })
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Nome da Categoria')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\Select::make('type')
                                            ->label('Tipo')
                                            ->options([
                                                'income' => 'Receita',
                                                'expense' => 'Despesa',
                                            ])
                                            ->required(),
                                        Forms\Components\Hidden::make('user_id')
                                            ->default(fn () => auth()->id()),
                                    ])
                                    ->createOptionUsing(function (array $data): int {
                                        return Category::create($data)->getKey();
                                    })
                                    ->columnSpan(['lg' => 1]),
                            ])
                            ->columns(2)
                            ->columnSpan(['lg' => 2]),

                        Forms\Components\Section::make('Datas')
                            ->description('Quando vence e quando foi pago')
                            ->icon('heroicon-o-calendar')
                            ->schema([
                                Forms\Components\DatePicker::make('due_at')
                                    ->label('Data de Vencimento')
                                    ->required()
                                    ->default(now())
                                    ->displayFormat('d/m/Y')
                                    ->native(false)
                                    ->closeOnDateSelection()
                                    ->columnSpan(['lg' => 1]),

                                Forms\Components\DatePicker::make('paid_at')
                                    ->label('Data de Pagamento')
                                    ->displayFormat('d/m/Y')
                                    ->native(false)
                                    ->closeOnDateSelection()
                                    ->columnSpan(['lg' => 1]),

                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'unpaid' => 'Não Pago',
                                        'paid' => 'Pago',
                                        'overdue' => 'Atrasado',
                                    ])
                                    ->default('unpaid')
                                    ->required()
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->columnSpan(['lg' => 1]),

                        Forms\Components\Section::make('Recorrência')
                            ->description('Configure se o lançamento se repete')
                            ->icon('heroicon-o-arrow-path')
                            ->schema([
                                Forms\Components\Select::make('period')
                                    ->label('Período')
                                    ->options([
                                        'single' => 'Único',
                                        'monthly' => 'Mensal',
                                        'yearly' => 'Anual',
                                    ])
                                    ->default('single')
                                    ->required()
                                    ->live()
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('enrollments')
                                    ->label('Número de Parcelas')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(120)
                                    ->placeholder('Ex: 12')
                                    ->visible(fn (Get $get) => in_array($get('period'), ['monthly', 'yearly']))
                                    ->columnSpan(['lg' => 1]),

                                Forms\Components\TextInput::make('enrollments_of')
                                    ->label('Parcela Atual')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->visible(fn (Get $get) => in_array($get('period'), ['monthly', 'yearly']))
                                    ->columnSpan(['lg' => 1]),
                            ])
                            ->columns(2)
                            ->columnSpan(['lg' => 1])
                            ->collapsed(),

                        Forms\Components\Hidden::make('user_id')
                            ->default(fn () => auth()->id()),
                    ])
                    ->columns(['lg' => 2]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('description')
                    ->label('Descrição')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->limit(30)
                    ->tooltip(fn (Invoice $record): string => $record->description),

                Tables\Columns\TextColumn::make('type')
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
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'income' => 'heroicon-o-arrow-trending-up',
                        'expense' => 'heroicon-o-arrow-trending-down',
                        default => 'heroicon-o-question-mark-circle',
                    }),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Valor')
                    ->formatStateUsing(fn (int $state, Invoice $record): string => 
                        ($record->type === 'expense' ? '- ' : '+ ') . 
                        'R$ ' . number_format($state / 100, 2, ',', '.')
                    )
                    ->color(fn (Invoice $record): string => $record->type === 'income' ? 'success' : 'danger')
                    ->weight(FontWeight::Bold)
                    ->sortable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Categoria')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('wallet.name')
                    ->label('Carteira')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_at')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn (Invoice $record): string => 
                        $record->status === 'paid' ? 'gray' : 
                        ($record->due_at < now() ? 'danger' : 'warning')
                    ),

                Tables\Columns\TextColumn::make('status')
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
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'unpaid' => 'heroicon-o-clock',
                        'paid' => 'heroicon-o-check-circle',
                        'overdue' => 'heroicon-o-exclamation-circle',
                        default => 'heroicon-o-question-mark-circle',
                    }),

                Tables\Columns\TextColumn::make('enrollments_display')
                    ->label('Parcela')
                    ->getStateUsing(fn (Invoice $record): string => 
                        $record->enrollments ? "{$record->enrollments_of}/{$record->enrollments}" : '-'
                    )
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('due_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'income' => 'Receita',
                        'expense' => 'Despesa',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'unpaid' => 'Não Pago',
                        'paid' => 'Pago',
                        'overdue' => 'Atrasado',
                    ]),

                Tables\Filters\SelectFilter::make('wallet_id')
                    ->label('Carteira')
                    ->relationship(
                        name: 'wallet',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query) => $query->where('user_id', auth()->id())
                    )
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Categoria')
                    ->relationship(
                        name: 'category',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query) => $query->where('user_id', auth()->id())
                    )
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('due_at')
                    ->form([
                        Forms\Components\DatePicker::make('due_from')
                            ->label('Vencimento de')
                            ->displayFormat('d/m/Y'),
                        Forms\Components\DatePicker::make('due_until')
                            ->label('Vencimento até')
                            ->displayFormat('d/m/Y'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['due_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('due_at', '>=', $date),
                            )
                            ->when(
                                $data['due_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('due_at', '<=', $date),
                            );
                    }),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->filtersFormColumns(3)
            ->actions([
                Tables\Actions\Action::make('pay')
                    ->label(function(Invoice $record) {
                        return $record->type === 'income' ? 'Receber' : 'Pagar';
                    })
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(function(Invoice $record) {
                        return $record->type === 'income' ? 'Confirmar Recebimento' : 'Confirmar Pagamento';
                    })
                    ->modalDescription(function(Invoice $record) {
                        return $record->type === 'income' ? 'Deseja marcar este lançamento como recebido?' : 'Deseja marcar este lançamento como pago?';
                    })
                    ->modalSubmitActionLabel(function(Invoice $record) {
                        return $record->type === 'income' ? 'Sim, marcar como recebido' : 'Sim, marcar como pago';
                    })
                    ->visible(fn (Invoice $record): bool => $record->status !== 'paid')
                    ->action(function (Invoice $record): void {
                        $record->update([
                            'status' => 'paid',
                            'paid_at' => now(),
                        ]);

                        // Atualiza o saldo da carteira
                        $wallet = $record->wallet;
                        if ($record->type === 'income') {
                            $wallet->balance += $record->amount;
                        } else {
                            $wallet->balance -= $record->amount;
                        }
                        $wallet->save();

                        Notification::make()
                            ->success()
                            ->title($record->type === 'income' ? 'Lançamento recebido!' : 'Lançamento pago!')
                            ->body($record->type === 'income' ? 'O lançamento foi marcado como recebido.' : 'O lançamento foi marcado como pago.')
                            ->send();
                    }),

                Tables\Actions\Action::make('unpay')
                    ->label('Desfazer')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading(function(Invoice $record) {
                        return $record->type === 'income' ? 'Confirmar Recebimento' : 'Confirmar Pagamento';
                    })
                    ->modalDescription(function(Invoice $record) {
                        return $record->type === 'income' ? 'Deseja desfazer o recebimento deste lançamento?' : 'Deseja desfazer o pagamento deste lançamento?';
                    })
                    ->modalSubmitActionLabel(function(Invoice $record) {
                        return $record->type === 'income' ? 'Sim, desfazer recebimento' : 'Sim, desfazer pagamento';
                    })
                    ->visible(fn (Invoice $record): bool => $record->status === 'paid')
                    ->action(function (Invoice $record): void {
                        // Reverte o saldo da carteira
                        $wallet = $record->wallet;
                        if ($record->type === 'income') {
                            $wallet->balance -= $record->amount;
                        } else {
                            $wallet->balance += $record->amount;
                        }
                        $wallet->save();

                        $record->update([
                            'status' => $record->due_at < now() ? 'overdue' : 'unpaid',
                            'paid_at' => null,
                        ]);

                        Notification::make()
                            ->warning()
                            ->title($record->type === 'income' ? 'Recebimento desfeito!' : 'Pagamento desfeito!')
                            ->body($record->type === 'income' ? 'O recebimento foi desfeito.' : 'O pagamento foi desfeito.')
                            ->send();
                    }),

                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                    Tables\Actions\ForceDeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('markAsPaid')
                        ->label('Marcar como Pago')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            foreach ($records as $record) {
                                if ($record->status !== 'paid') {
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
                                }
                            }

                            Notification::make()
                                ->success()
                                ->title('Lançamentos pagos!')
                                ->body('Os lançamentos selecionados foram marcados como pagos.')
                                ->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Nenhum lançamento encontrado')
            ->emptyStateDescription('Crie seu primeiro lançamento para começar a controlar suas finanças.')
            ->emptyStateIcon('heroicon-o-document-text')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Criar Lançamento')
                    ->icon('heroicon-o-plus'),
            ])
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'view' => Pages\ViewInvoice::route('/{record}'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id())
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('user_id', auth()->id())
            ->where('status', 'unpaid')
            ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::where('user_id', auth()->id())
            ->where('status', 'overdue')
            ->count();

        return $count > 0 ? 'danger' : 'warning';
    }
}

