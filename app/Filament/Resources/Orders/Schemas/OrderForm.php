<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('status')
                    ->options(OrderStatus::class)
                    ->required()
                    ->disabled(fn (?Order $record): bool => $record?->status === OrderStatus::Cancelled)
                    ->helperText('Bekor qilinsa, mahsulotlar ombor qoldig\'iga qaytadi. Bekor qilingan buyurtmani qayta ochib bo\'lmaydi.'),
                TextInput::make('customer_name')->disabled(),
                TextInput::make('phone')->disabled(),
                TextInput::make('delivery_method')->disabled(),
                TextInput::make('payment_method')->disabled(),
                TextInput::make('address')->disabled()->columnSpanFull(),
                Textarea::make('note')->disabled()->columnSpanFull(),
                TextInput::make('subtotal')->prefix('$')->disabled(),
                TextInput::make('tax')->prefix('$')->disabled(),
                TextInput::make('delivery_fee')->prefix('$')->disabled(),
                TextInput::make('total_price')->prefix('$')->disabled(),
            ]);
    }
}
