<?php

namespace Fayne\Event\Filament\Resources;

use Fayne\Event\Filament\Resources\BatchInsertEventSlotRecordResource\Pages;
use Fayne\Event\Models\BatchInsertEventSlotRecord;
use Fayne\Event\Support\EventConfig;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BatchInsertEventSlotRecordResource extends Resource
{
    public static function getNavigationLabel(): string
    {
        return __('filament-event::navigation.batch_insert_records');
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('filament-cms::navigation.events');
    }

    protected static bool $isScopedToTenant = false;
    protected static ?string $model = BatchInsertEventSlotRecord::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-date-range';

    public static function form(Schema $schema): Schema
    {
        $eventModel = EventConfig::getEventModel();
        $storeModel = EventConfig::getStoreModel();

        return $schema->components([
            Select::make('event_id')->options(fn () => $eventModel::query()->orderBy('title')->pluck('title', 'id'))->searchable()->required(),
            Select::make('store_ids')->options(fn () => $storeModel::query()->orderBy('name')->pluck('name', 'id'))->multiple()->searchable()->required(),
            DatePicker::make('from')->required(),
            DatePicker::make('till')->required()->afterOrEqual('from'),
            CheckboxList::make('week')->options([0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'])->required(),
            TextInput::make('start_time')->type('time')->required(),
            TextInput::make('end_time')->type('time')->required(),
            TextInput::make('stock')->numeric()->minValue(0)->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('event')->label('Event'),
            TextColumn::make('from')->date(),
            TextColumn::make('till')->date(),
            TextColumn::make('start_time'),
            TextColumn::make('end_time'),
            TextColumn::make('stock'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBatchInsertEventSlotRecords::route('/'),
            'create' => Pages\CreateBatchInsertEventSlotRecord::route('/create'),
            'edit' => Pages\EditBatchInsertEventSlotRecord::route('/{record}/edit'),
        ];
    }
}
