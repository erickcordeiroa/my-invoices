<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use App\Models\ProductVariation;

class VariationsRelationManager extends RelationManager
{
    protected static string $relationship = 'variations';

    protected static ?string $title = 'Variações';

    protected static ?string $modelLabel = 'Variação';

    protected static ?string $pluralModelLabel = 'Variações';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados da Variação')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome da Variação')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ex: Azul - P, Vermelho - M')
                            ->columnSpan(['lg' => 2]),

                        Forms\Components\TextInput::make('sku')
                            ->label('SKU da Variação')
                            ->required()
                            ->maxLength(100)
                            ->unique(ignoreRecord: true)
                            ->placeholder('PROD-001-AZ-P')
                            ->columnSpan(['lg' => 2]),

                        Forms\Components\ColorPicker::make('color')
                            ->label('Cor'),

                        Forms\Components\TextInput::make('size')
                            ->label('Tamanho')
                            ->maxLength(50)
                            ->placeholder('P, M, G, GG, 38, 40...'),

                        Forms\Components\TextInput::make('price')
                            ->label('Preço')
                            ->required()
                            ->prefix('R$')
                            ->mask(\Filament\Support\RawJs::make(<<<'JS'
                                $money($input, ',', '.', 2)
                            JS))
                            ->stripCharacters('.')
                            ->dehydrateStateUsing(function ($state) {
                                if (empty($state)) return 0;
                                $value = str_replace('.', '', $state);
                                $value = str_replace(',', '.', $value);
                                return (int) round(floatval($value) * 100);
                            })
                            ->formatStateUsing(function ($state) {
                                if (empty($state) || !is_numeric($state)) return '0,00';
                                return number_format((int) $state / 100, 2, ',', '.');
                            })
                            ->placeholder('0,00')
                            ->helperText('Deixe igual ao produto principal se não tiver diferença'),

                        Forms\Components\TextInput::make('stock')
                            ->label('Estoque')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->required(),

                        Forms\Components\TextInput::make('min_stock')
                            ->label('Estoque Mínimo')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Ativo')
                            ->default(true)
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('images')
                            ->label('Imagens da Variação')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->appendFiles()
                            ->maxFiles(3)
                            ->disk('public')
                            ->directory(fn () => 'usuarios/' . auth()->id() . '/variacoes')
                            ->visibility('public')
                            ->imageEditor()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(2048)
                            ->columnSpanFull(),
                    ])
                    ->columns(4),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\ImageColumn::make('images')
                    ->label('Foto')
                    ->circular()
                    ->stacked()
                    ->limit(1)
                    ->disk('public'),

                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight(FontWeight::Bold)
                    ->color('gray'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Variação')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),

                Tables\Columns\ColorColumn::make('color')
                    ->label('Cor'),

                Tables\Columns\TextColumn::make('size')
                    ->label('Tamanho')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('price')
                    ->label('Preço')
                    ->formatStateUsing(fn (int $state): string => 'R$ ' . number_format($state / 100, 2, ',', '.'))
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->color('success'),

                Tables\Columns\TextColumn::make('stock')
                    ->label('Estoque')
                    ->sortable()
                    ->badge()
                    ->color(fn (ProductVariation $record): string => 
                        $record->stock <= 0 ? 'danger' : 
                        ($record->stock <= $record->min_stock ? 'warning' : 'success')
                    )
                    ->icon(fn (ProductVariation $record): string => 
                        $record->stock <= 0 ? 'heroicon-o-x-circle' : 
                        ($record->stock <= $record->min_stock ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle')
                    ),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Nova Variação')
                    ->icon('heroicon-o-plus'),
            ])
            ->actions([
                Tables\Actions\Action::make('adjust_stock')
                    ->label('Ajustar')
                    ->icon('heroicon-o-archive-box-arrow-down')
                    ->color('warning')
                    ->modalHeading('Ajustar Estoque')
                    ->form([
                        Forms\Components\Select::make('operation')
                            ->label('Operação')
                            ->options([
                                'add' => 'Adicionar',
                                'remove' => 'Remover',
                                'set' => 'Definir',
                            ])
                            ->required()
                            ->default('add')
                            ->native(false),

                        Forms\Components\TextInput::make('quantity')
                            ->label('Quantidade')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->default(1),
                    ])
                    ->action(function (ProductVariation $record, array $data): void {
                        $oldStock = $record->stock;
                        
                        switch ($data['operation']) {
                            case 'add':
                                $record->stock += $data['quantity'];
                                break;
                            case 'remove':
                                $record->stock = max(0, $record->stock - $data['quantity']);
                                break;
                            case 'set':
                                $record->stock = $data['quantity'];
                                break;
                        }
                        
                        $record->save();

                        Notification::make()
                            ->success()
                            ->title('Estoque atualizado!')
                            ->body("Estoque alterado de {$oldStock} para {$record->stock}")
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Nenhuma variação cadastrada')
            ->emptyStateDescription('Adicione variações como cores e tamanhos diferentes.')
            ->emptyStateIcon('heroicon-o-squares-2x2');
    }
}
