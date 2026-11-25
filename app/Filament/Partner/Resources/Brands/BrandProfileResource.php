<?php

namespace App\Filament\Partner\Resources\Brands;

use App\Filament\Partner\Resources\Brands\Pages\EditBrandProfile;
use App\Filament\Partner\Resources\Brands\Pages\ListBrandProfiles;
use App\Filament\Partner\Resources\Brands\Schemas\BrandProfileForm;
use App\Models\Brand;
use App\Services\PartnerService;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BrandProfileResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static ?string $slug = 'brand-profile';

    protected static ?string $navigationLabel = 'Brand profile';

    protected static ?string $modelLabel = 'brand profile';

    protected static ?string $pluralModelLabel = 'brand profiles';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    public static function form(Schema $schema): Schema
    {
        return BrandProfileForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo_url')->label('Logo')->square(),
                TextColumn::make('name')->sortable(),
                TextColumn::make('tagline')->limit(50)->placeholder('-'),
                IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $ids = app(PartnerService::class)->brandIds(auth()->user());

        return $ids === null ? $query : $query->whereIn('brands.id', $ids);
    }

    /** Yangi brendni faqat admin ochadi. */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBrandProfiles::route('/'),
            'edit' => EditBrandProfile::route('/{record}/edit'),
        ];
    }
}
