<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WalletResource\Pages;
use App\Models\Wallet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WalletResource extends Resource
{
    protected static ?string $model = Wallet::class;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    protected static ?string $navigationGroup = 'Financeiro';

    protected static ?string $modelLabel = 'Carteira';

    protected static ?string $pluralModelLabel = 'Carteiras';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações da Carteira')
                    ->description('Configure sua carteira para controle financeiro')
                    ->icon('heroicon-o-wallet')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome da Carteira')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ex: Conta Corrente, Poupança, Carteira...')
                            ->autofocus()
                            ->columnSpanFull(),
                        
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
                            ->helperText('Informe o saldo atual desta carteira')
                            ->columnSpanFull(),

                        Forms\Components\Hidden::make('user_id')
                            ->default(fn () => auth()->id()),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-wallet'),
                
                Tables\Columns\TextColumn::make('balance')
                    ->label('Saldo')
                    ->formatStateUsing(fn (int $state): string => 'R$ ' . number_format($state / 100, 2, ',', '.'))
                    ->color(fn (int $state): string => $state >= 0 ? 'success' : 'danger')
                    ->weight('bold')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name', 'asc')
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
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
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Nenhuma carteira encontrada')
            ->emptyStateDescription('Crie sua primeira carteira para começar a gerenciar seu dinheiro.')
            ->emptyStateIcon('heroicon-o-wallet')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Criar Carteira')
                    ->icon('heroicon-o-plus'),
            ]);
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
            'index' => Pages\ListWallets::route('/'),
            'create' => Pages\CreateWallet::route('/create'),
            'edit' => Pages\EditWallet::route('/{record}/edit'),
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
        return static::getModel()::where('user_id', auth()->id())->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
}

