<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages\ManageProducts;
use App\Models\Product;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static string|\UnitEnum|null $navigationGroup = 'Catalog';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->columnSpan(2),
            Forms\Components\TextInput::make('sku')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('barcode')
                ->maxLength(255),
            Forms\Components\Select::make('category_id')
                ->relationship('category', 'name')
                ->searchable()
                ->preload(),
            Forms\Components\Select::make('tax_category_id')
                ->relationship('taxCategory', 'name')
                ->required()
                ->searchable()
                ->preload(),
            Forms\Components\TextInput::make('base_price')
                ->numeric()
                ->required()
                ->prefix('KES'),
            Forms\Components\TextInput::make('cost_price')
                ->numeric()
                ->required()
                ->prefix('KES'),
            Forms\Components\TextInput::make('stock_quantity')
                ->numeric()
                ->required(),
            Forms\Components\Toggle::make('allow_fractional_sales'),
            Forms\Components\DatePicker::make('last_received_date'),
            Forms\Components\DatePicker::make('batch_expiry_date'),
        ])->columns(4);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['category', 'taxCategory']))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sku')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('base_price')
                    ->label('Price')
                    ->formatStateUsing(fn ($state): string => 'KES ' . number_format((float) $state, 2))
                    ->alignEnd()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cost_price')
                    ->label('Cost')
                    ->formatStateUsing(fn ($state): string => 'KES ' . number_format((float) $state, 2))
                    ->alignEnd()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->numeric(decimalPlaces: 2)
                    ->alignEnd()
                    ->sortable(),
                Tables\Columns\TextColumn::make('batch_expiry_date')
                    ->date('d M Y')
                    ->label('Expiry'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('critical_stock')
                    ->label('Critical stock')
                    ->queries(
                        true: fn (Builder $query) => $query->where('stock_quantity', '<', 10),
                        false: fn (Builder $query) => $query->where('stock_quantity', '>=', 10),
                        blank: fn (Builder $query) => $query,
                    ),
            ])
            ->paginated([50, 100, 250])
            ->defaultPaginationPageOption(100)
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageProducts::route('/'),
        ];
    }
}
