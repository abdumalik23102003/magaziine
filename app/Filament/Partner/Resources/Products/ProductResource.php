<?php

namespace App\Filament\Partner\Resources\Products;

use App\Filament\Partner\Resources\Products\Pages\CreateProduct;
use App\Filament\Partner\Resources\Products\Pages\EditProduct;
use App\Filament\Partner\Resources\Products\Pages\ListProducts;
use App\Filament\Partner\Resources\Products\Schemas\ProductForm;
use App\Filament\Partner\Resources\Products\Tables\ProductsTable;
use App\Filament\Resources\Products\RelationManagers\ImagesRelationManager;
use App\Filament\Resources\Products\RelationManagers\VariantsRelationManager;
use App\Models\Product;
use App\Services\PartnerService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    /** Faqat o'z brendlari. Record URL ham shu query orqali topiladi: boshqa brend mahsuloti 404. */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['brand', 'mainImage']);
        $ids = app(PartnerService::class)->brandIds(auth()->user());

        return $ids === null ? $query : $query->whereIn('brand_id', $ids);
    }

    public static function getRelations(): array
    {
        return [
            VariantsRelationManager::class,
            ImagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
