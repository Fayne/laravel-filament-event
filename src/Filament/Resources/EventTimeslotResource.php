<?php

namespace Fayne\Event\Filament\Resources;

use Fayne\Event\Filament\Resources\EventTimeslotResource\Pages;
use Fayne\Event\Models\EventTimeslot;
use Fayne\Event\Support\EventConfig;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EventTimeslotResource extends Resource
{
    public static function getNavigationLabel(): string
    {
        return __('filament-event::navigation.event_timeslots');
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('filament-cms::navigation.events');
    }

    protected static bool $isScopedToTenant = false;
    protected static ?string $model = EventTimeslot::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    public static function form(Schema $schema): Schema
    {
        $storeModel = EventConfig::getStoreModel();

        return $schema->components([
            Select::make('event_id')->relationship('event', 'title')->required()->searchable()->preload(),
            Select::make('store_id')->options(fn () => $storeModel::query()->orderBy('name')->pluck('name', 'id'))->searchable(),
            DatePicker::make('date')->required(),
            TextInput::make('start_time')->type('time')->required(),
            TextInput::make('end_time')->type('time')->required(),
            TextInput::make('stock')->numeric()->minValue(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('event.title')->searchable(),
            TextColumn::make('store.name'),
            TextColumn::make('date')->date()->sortable(),
            TextColumn::make('start_time'),
            TextColumn::make('end_time'),
            TextColumn::make('stock'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEventTimeslots::route('/'),
            'create' => Pages\CreateEventTimeslot::route('/create'),
            'edit' => Pages\EditEventTimeslot::route('/{record}/edit'),
        ];
    }
}
