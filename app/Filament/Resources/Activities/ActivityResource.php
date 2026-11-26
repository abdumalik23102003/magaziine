<?php

namespace App\Filament\Resources\Activities;

use App\Filament\Resources\Activities\Pages\ListActivities;
use App\Models\AuditLog;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** Faqat o'qish: yozuv yaratib, tahrirlab yoki o'chirib bo'lmaydi. */
class ActivityResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $slug = 'activity-log';

    protected static ?string $navigationLabel = 'Activity log';

    protected static ?string $modelLabel = 'activity';

    protected static ?string $pluralModelLabel = 'activity log';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('When')->dateTime()->sortable(),
                TextColumn::make('actor.name')->label('Actor')->placeholder('system'),
                TextColumn::make('action')->badge()->searchable(),
                TextColumn::make('subject_type')
                    ->label('Subject')
                    ->state(fn (AuditLog $record): string => class_basename((string) $record->subject_type).' #'.$record->subject_id),
                TextColumn::make('new_values')
                    ->label('Change')
                    ->state(fn (AuditLog $record): string => $record->diffText())
                    ->wrap(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('action')
                    ->options(fn (): array => AuditLog::query()->distinct()->orderBy('action')->pluck('action', 'action')->all()),
                SelectFilter::make('actor_id')->label('Actor')->relationship('actor', 'name'),
                Filter::make('created_at')
                    ->schema([DatePicker::make('from'), DatePicker::make('until')])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date))),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('actor');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivities::route('/'),
        ];
    }
}
