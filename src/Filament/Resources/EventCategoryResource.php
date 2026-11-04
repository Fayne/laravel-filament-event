<?php

namespace Fayne\Event\Filament\Resources;

use Fayne\Event\Filament\Resources\EventCategoryResource\Pages;
use Fayne\Event\Models\EventCategory;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EventCategoryResource extends Resource
{
    public static function getNavigationLabel(): string
    {
        return __('filament-event::navigation.event_categories');
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('filament-cms::navigation.events');
    }

    protected static bool $isScopedToTenant = false;
    protected static ?string $model = EventCategory::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('locale')->default(\Fayne\CMS\Support\ContentLocale::default())->required()->disabled()->dehydrated(false),
            TextInput::make('title')->required()->unique(ignoreRecord: true),
            TextInput::make('subtitle'),
            Textarea::make('description'),
            TextInput::make('weight')->numeric()->default(99)->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('weight')->columns([
            TextColumn::make('title')->searchable()->sortable(),
            TextColumn::make('locale'),
            TextColumn::make('weight')->sortable(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEventCategories::route('/'),
            'create' => Pages\CreateEventCategory::route('/create'),
            'edit' => Pages\EditEventCategory::route('/{record}/edit'),
        ];
    }
}
