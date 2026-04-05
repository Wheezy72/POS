<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\IncomingMpesaPaymentResource\Pages\ManageIncomingMpesaPayments;
use App\Models\IncomingMpesaPayment;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class IncomingMpesaPaymentResource extends Resource
{
    protected static ?string $model = IncomingMpesaPayment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static string|\UnitEnum|null $navigationGroup = 'Payments';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Placeholder::make('read_only')
                ->content('Incoming M-PESA payments arrive through the C2B webhook and stay auditable here.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query)
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('transaction_code')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('customer_name')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('amount')
                    ->formatStateUsing(fn ($state): string => 'KES ' . number_format((float) $state, 2))
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('claimed_at')
                    ->since()
                    ->placeholder('Pending'),
            ])
            ->filters([
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $builder, $date) => $builder->whereDate('created_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $builder, $date) => $builder->whereDate('created_at', '<=', $date));
                    }),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'claimed' => 'Claimed',
                    ]),
            ])
            ->paginated([50, 100, 250])
            ->defaultPaginationPageOption(100);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageIncomingMpesaPayments::route('/'),
        ];
    }
}
