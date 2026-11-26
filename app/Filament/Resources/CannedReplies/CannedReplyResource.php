<?php

namespace App\Filament\Resources\CannedReplies;

use App\Filament\Resources\CannedReplies\Pages\ManageCannedReplies;
use App\Models\CannedReply;
use App\Support\Staff;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CannedReplyResource extends Resource
{
    protected static ?string $model = CannedReply::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bolt';

    protected static ?string $navigationLabel = 'Tayyor javoblar';

    protected static ?int $navigationSort = 6;

    public static function canViewAny(): bool
    {
        return Staff::isOperator(auth()->user());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->label('Sarlavha')->required()->maxLength(120),
            Textarea::make('body')->label('Matn')->required()->maxLength((int) config('support.max_length'))->rows(4),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Sarlavha')->searchable(),
                TextColumn::make('body')->label('Matn')->limit(70),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageCannedReplies::route('/')];
    }
}
