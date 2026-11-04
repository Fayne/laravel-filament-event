<?php

namespace Fayne\Event\Filament\Resources;

use Fayne\Event\Filament\Resources\EventRuleResource\Pages\ListEventRules;
use Fayne\Event\Models\EventRule;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EventRuleResource extends Resource
{
    public static function getNavigationLabel(): string
    {
        return __('filament-event::navigation.event_rules');
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('filament-cms::navigation.events');
    }

    protected static bool $isScopedToTenant = false;
    protected static ?string $model = EventRule::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable(),
            TextColumn::make('event.title')->searchable(),
            TextColumn::make('condition'),
            TextColumn::make('action_type'),
            TextColumn::make('weight')->sortable(),
            TextColumn::make('conditions')->formatStateUsing(fn ($state) => json_encode($state, JSON_UNESCAPED_UNICODE)),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListEventRules::route('/')];
    }
}
