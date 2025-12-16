<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Produtos';

    protected static ?string $modelLabel = 'Produto';

    protected static ?string $pluralModelLabel = 'Produtos';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Produto')
                    ->tabs([
                        // Tab 1: Informações Básicas
                        Forms\Components\Tabs\Tab::make('Informações')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\Grid::make()
                                    ->schema([
                                        Forms\Components\Section::make('Dados do Produto')
                                            ->schema([
                                                Forms\Components\TextInput::make('title')
                                                    ->label('Título')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                                        if (!$get('slug') || $get('slug') === Str::slug($get('title'))) {
                                                            $set('slug', Str::slug($state));
                                                        }
                                                    })
                                                    ->placeholder('Nome do produto')
                                                    ->autofocus()
                                                    ->columnSpan(2),

                                                Forms\Components\TextInput::make('sku')
                                                    ->label('SKU')
                                                    ->required()
                                                    ->maxLength(100)
                                                    ->unique(ignoreRecord: true)
                                                    ->placeholder('PROD-001')
                                                    ->helperText('Código único do produto'),

                                                Forms\Components\TextInput::make('slug')
                                                    ->label('Slug')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->unique(ignoreRecord: true)
                                                    ->placeholder('slug-do-produto')
                                                    ->helperText('URL amigável'),

                                                Forms\Components\RichEditor::make('description')
                                                    ->label('Descrição')
                                                    ->placeholder('Descreva o produto...')
                                                    ->toolbarButtons([
                                                        'bold',
                                                        'italic',
                                                        'underline',
                                                        'strike',
                                                        'bulletList',
                                                        'orderedList',
                                                        'redo',
                                                        'undo',
                                                    ])
                                                    ->columnSpanFull(),
                                            ])
                                            ->columns(4),
                                    ]),
                            ]),

                        // Tab 2: Preços e Estoque
                        Forms\Components\Tabs\Tab::make('Preços e Estoque')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Forms\Components\Grid::make()
                                    ->schema([
                                        Forms\Components\Section::make('Preços')
                                            ->description('Valores do produto')
                                            ->icon('heroicon-o-banknotes')
                                            ->schema([
                                                Forms\Components\TextInput::make('price')
                                                    ->label('Preço de Venda')
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
                                                    ->placeholder('0,00'),

                                                Forms\Components\TextInput::make('cost_price')
                                                    ->label('Preço de Custo')
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
                                                    ->helperText('Para calcular margem de lucro'),
                                            ])
                                            ->columns(2)
                                            ->columnSpan(1),

                                        Forms\Components\Section::make('Estoque')
                                            ->description('Controle de inventário')
                                            ->icon('heroicon-o-archive-box')
                                            ->schema([
                                                Forms\Components\TextInput::make('stock')
                                                    ->label('Estoque Atual')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->minValue(0)
                                                    ->required(),

                                                Forms\Components\TextInput::make('min_stock')
                                                    ->label('Estoque Mínimo')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->minValue(0)
                                                    ->helperText('Alerta quando atingir'),

                                                Forms\Components\Select::make('unit')
                                                    ->label('Unidade de Medida')
                                                    ->options([
                                                        'un' => 'Unidade (un)',
                                                        'kg' => 'Quilograma (kg)',
                                                        'g' => 'Grama (g)',
                                                        'l' => 'Litro (L)',
                                                        'ml' => 'Mililitro (ml)',
                                                        'm' => 'Metro (m)',
                                                        'cm' => 'Centímetro (cm)',
                                                        'cx' => 'Caixa (cx)',
                                                        'pc' => 'Peça (pç)',
                                                        'par' => 'Par',
                                                        'kit' => 'Kit',
                                                    ])
                                                    ->default('un')
                                                    ->required()
                                                    ->native(false)
                                                    ->columnSpanFull(),
                                            ])
                                            ->columns(2)
                                            ->columnSpan(1),
                                    ])
                                    ->columns(2),
                            ]),

                        // Tab 3: Atributos
                        Forms\Components\Tabs\Tab::make('Atributos')
                            ->icon('heroicon-o-tag')
                            ->schema([
                                Forms\Components\Section::make('Características')
                                    ->description('Atributos e características do produto')
                                    ->schema([
                                        Forms\Components\TextInput::make('brand')
                                            ->label('Marca')
                                            ->maxLength(100)
                                            ->placeholder('Ex: Nike, Apple, Samsung...')
                                            ->datalist(fn () => Product::where('user_id', auth()->id())
                                                ->whereNotNull('brand')
                                                ->distinct()
                                                ->pluck('brand')
                                                ->toArray()
                                            ),

                                        Forms\Components\ColorPicker::make('color')
                                            ->label('Cor Principal'),

                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Produto Ativo')
                                            ->default(true)
                                            ->helperText('Produtos inativos não aparecem para venda')
                                            ->inline(false),

                                        Forms\Components\Toggle::make('has_variations')
                                            ->label('Possui Variações')
                                            ->default(false)
                                            ->helperText('Ative para cadastrar variações de cor, tamanho, etc.')
                                            ->inline(false),
                                    ])
                                    ->columns(2),
                            ]),

                        // Tab 4: Imagens
                        Forms\Components\Tabs\Tab::make('Imagens')
                            ->icon('heroicon-o-photo')
                            ->schema([
                                Forms\Components\Section::make('Galeria de Imagens')
                                    ->description('Adicione até 5 imagens do produto')
                                    ->schema([
                                        Forms\Components\FileUpload::make('images')
                                            ->label('Imagens do Produto')
                                            ->image()
                                            ->multiple()
                                            ->reorderable()
                                            ->appendFiles()
                                            ->maxFiles(5)
                                            ->disk('public')
                                            ->directory(fn () => 'usuarios/' . auth()->id() . '/produtos')
                                            ->visibility('public')
                                            ->imageEditor()
                                            ->imageEditorAspectRatios([
                                                '1:1',
                                                '4:3',
                                                '16:9',
                                            ])
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                            ->maxSize(2048)
                                            ->helperText('Formatos aceitos: JPG, PNG, WebP. Tamanho máximo: 2MB por imagem.')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString(),

                Forms\Components\Hidden::make('user_id')
                    ->default(fn () => auth()->id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('images')
                    ->label('Foto')
                    ->circular()
                    ->stacked()
                    ->limit(1)
                    ->disk('public')
                    ->defaultImageUrl(fn () => 'https://ui-avatars.com/api/?name=P&background=10b981&color=fff'),

                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight(FontWeight::Bold)
                    ->color('gray'),

                Tables\Columns\TextColumn::make('title')
                    ->label('Produto')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->limit(30)
                    ->tooltip(fn (Product $record): string => $record->title),

                Tables\Columns\TextColumn::make('brand')
                    ->label('Marca')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\ColorColumn::make('color')
                    ->label('Cor')
                    ->toggleable(),

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
                    ->color(fn (Product $record): string => 
                        $record->stock <= 0 ? 'danger' : 
                        ($record->stock <= $record->min_stock ? 'warning' : 'success')
                    )
                    ->icon(fn (Product $record): string => 
                        $record->stock <= 0 ? 'heroicon-o-x-circle' : 
                        ($record->stock <= $record->min_stock ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle')
                    )
                    ->formatStateUsing(fn (Product $record): string => 
                        $record->stock . ' ' . $record->unit
                    ),

                Tables\Columns\TextColumn::make('min_stock')
                    ->label('Mín.')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('variations_count')
                    ->counts('variations')
                    ->label('Variações')
                    ->badge()
                    ->color('info')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('title', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Ativos',
                        '0' => 'Inativos',
                    ]),

                Tables\Filters\Filter::make('low_stock')
                    ->label('Estoque Baixo')
                    ->query(fn (Builder $query): Builder => $query->whereColumn('stock', '<=', 'min_stock'))
                    ->toggle(),

                Tables\Filters\Filter::make('out_of_stock')
                    ->label('Sem Estoque')
                    ->query(fn (Builder $query): Builder => $query->where('stock', '<=', 0))
                    ->toggle(),

                Tables\Filters\SelectFilter::make('brand')
                    ->label('Marca')
                    ->options(fn () => Product::where('user_id', auth()->id())
                        ->whereNotNull('brand')
                        ->distinct()
                        ->pluck('brand', 'brand')
                    )
                    ->searchable(),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->filtersFormColumns(2)
            ->actions([
                Tables\Actions\Action::make('adjust_stock')
                    ->label('Ajustar')
                    ->icon('heroicon-o-archive-box-arrow-down')
                    ->color('warning')
                    ->modalHeading('Ajustar Estoque')
                    ->modalDescription(fn (Product $record): string => "Ajustar estoque de: {$record->title}")
                    ->form([
                        Forms\Components\Select::make('operation')
                            ->label('Operação')
                            ->options([
                                'add' => 'Adicionar ao estoque',
                                'remove' => 'Remover do estoque',
                                'set' => 'Definir estoque',
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

                        Forms\Components\Textarea::make('reason')
                            ->label('Motivo')
                            ->placeholder('Ex: Entrada de mercadoria, Correção...')
                            ->maxLength(255),
                    ])
                    ->action(function (Product $record, array $data): void {
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
                            ->body("Estoque alterado de {$oldStock} para {$record->stock} {$record->unit}")
                            ->send();
                    }),

                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('toggle_active')
                        ->label(fn (Product $record): string => $record->is_active ? 'Desativar' : 'Ativar')
                        ->icon(fn (Product $record): string => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                        ->color(fn (Product $record): string => $record->is_active ? 'danger' : 'success')
                        ->requiresConfirmation()
                        ->action(function (Product $record): void {
                            $record->is_active = !$record->is_active;
                            $record->save();

                            Notification::make()
                                ->success()
                                ->title($record->is_active ? 'Produto ativado!' : 'Produto desativado!')
                                ->send();
                        }),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                    Tables\Actions\ForceDeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Ativar Selecionados')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            $records->each(fn ($record) => $record->update(['is_active' => true]));
                            
                            Notification::make()
                                ->success()
                                ->title('Produtos ativados!')
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Desativar Selecionados')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            $records->each(fn ($record) => $record->update(['is_active' => false]));
                            
                            Notification::make()
                                ->warning()
                                ->title('Produtos desativados!')
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Nenhum produto encontrado')
            ->emptyStateDescription('Cadastre seu primeiro produto para começar.')
            ->emptyStateIcon('heroicon-o-cube')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Cadastrar Produto')
                    ->icon('heroicon-o-plus'),
            ])
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\VariationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
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
            ->where('is_active', true)
            ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $lowStock = static::getModel()::where('user_id', auth()->id())
            ->whereColumn('stock', '<=', 'min_stock')
            ->count();

        return $lowStock > 0 ? 'warning' : 'primary';
    }
}
