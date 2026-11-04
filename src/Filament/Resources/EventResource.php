<?php

namespace Fayne\Event\Filament\Resources;

use Fayne\Event\Enums\EventStockType;
use Fayne\Event\Filament\Resources\EventResource\Pages;
use Fayne\Event\Models\Event;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class EventResource extends Resource
{
    public static function getNavigationLabel(): string
    {
        return __('filament-event::navigation.events_resource');
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('filament-cms::navigation.events');
    }

    protected static bool $isScopedToTenant = false;
    protected static ?string $model = Event::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('public_id')->default(fn () => (string) Str::ulid())->required(),
            TextInput::make('locale')->default(\Fayne\CMS\Support\ContentLocale::default())->required()->disabled()->dehydrated(false),
            Select::make('event_category_id')->relationship('category', 'title')->searchable()->preload(),
            TextInput::make('internal_title')->required()->unique(ignoreRecord: true),
            TextInput::make('title')->required(),
            TextInput::make('slug'),
            TextInput::make('subtitle'),
            Textarea::make('description')->columnSpanFull(),
            Select::make('stores')->relationship('stores', 'name')->multiple()->preload(),
            Toggle::make('shown_in_homepage')->default(true),
            Toggle::make('status')->default(false),
            Toggle::make('is_bookable'),
            Toggle::make('is_charged'),
            Select::make('booking_auth_mode')->options(['both' => 'Guest and account', 'guest' => 'Guest', 'authenticated' => 'Authenticated'])->required(),
            Select::make('stock_type')->options(collect(EventStockType::cases())->mapWithKeys(fn (EventStockType $type) => [$type->value => $type->name]))->required(),
            TextInput::make('stock')->numeric()->minValue(0),
            TextInput::make('currency')->default('CNY')->required(),
            TextInput::make('price')->numeric()->minValue(0),
            DateTimePicker::make('start_datetime'),
            DateTimePicker::make('end_datetime'),
            Toggle::make('need_verify'),
            Textarea::make('redemption_code'),
            TextInput::make('weight')->numeric()->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('weight')->columns([
            TextColumn::make('internal_title')->searchable(),
            TextColumn::make('title')->searchable(),
            TextColumn::make('locale'),
            IconColumn::make('status')->boolean(),
            IconColumn::make('is_bookable')->boolean(),
            TextColumn::make('weight')->sortable(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }
}
