<?php

namespace App\Filament\Partner\Resources\Orders;

use App\Enums\UserRole;
use App\Filament\Partner\Resources\Orders\Pages\ListOrderItems;
use App\Models\OrderItem;
use App\Services\PartnerService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/** Faqat o'qish: hamkorning o'z mahsulotlari kirgan buyurtma qatorlari. */
class OrderItemResource extends Resource
{
    protected static ?string $model = OrderItem::class;

    protected static ?string $slug = 'orders';

    protected static ?string $navigationLabel = 'Orders';

    protected static ?string $modelLabel = 'order line';

    protected static ?string $pluralModelLabel = 'orders';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    public static function getEloquentQuery(): Builder
    {
        return app(PartnerService::class)
            ->orderItems(auth()->user())
            ->with('order:id,customer_name,address,status,created_at');
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->hasRole(UserRole::Partner, UserRole::Admin);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order.id')
                    ->label('Order')
                    ->formatStateUsing(fn ($state): string => 'FM-'.str_pad((string) $state, 5, '0', STR_PAD_LEFT)),
                TextColumn::make('order.customer_name')
                    ->label('Customer'),
                TextColumn::make('order.address')
                    ->label('City')
                    ->formatStateUsing(fn ($state): string => Str::afterLast((string) $state, ', ')),
                TextColumn::make('order.status')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('product_name')
                    ->label('Product')
                    ->searchable(),
                TextColumn::make('variant_name')
                    ->label('Variant'),
                TextColumn::make('quantity')
                    ->numeric(),
                TextColumn::make('price')
                    ->money('USD'),
                TextColumn::make('line_total')
                    ->label('Total')
                    ->state(fn (OrderItem $record): float => (float) $record->price * (float) $record->quantity)
                    ->money('USD'),
                TextColumn::make('order.created_at')
                    ->label('Date')
                    ->dateTime(),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrderItems::route('/'),
        ];
    }
}
