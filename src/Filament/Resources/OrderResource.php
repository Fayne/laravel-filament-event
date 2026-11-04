<?php

namespace Fayne\Event\Filament\Resources;

use Fayne\Event\Models\Order;
use Fayne\Event\Enums\OrderStatus;
use Fayne\Event\Services\ReservationService;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;

class OrderResource extends Resource
{
    public static function getNavigationLabel(): string
    {
        return __('filament-event::navigation.orders');
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('filament-cms::navigation.events');
    }

    protected static bool $isScopedToTenant = false;
    protected static ?string $model = Order::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('out_trade_no')->searchable(),
            TextColumn::make('event.title')->searchable(),
            TextColumn::make('user.email'),
            TextColumn::make('status')->badge(),
            TextColumn::make('quantity'),
            TextColumn::make('total_amount'),
            TextColumn::make('created_at')->dateTime()->sortable(),
        ])->recordActions([
            Action::make('checkIn')
                ->label(__('filament-event::orders.check_in'))
                ->requiresConfirmation()
                ->visible(fn (Order $record): bool => $record->event?->need_verify
                    && ! $record->check_in_at
                    && ! in_array($record->status, [OrderStatus::Cancelled, OrderStatus::CheckedIn], true))
                ->action(function (Order $record): void {
                    Gate::authorize('update', $record);
                    app(ReservationService::class)->checkInByCode(
                        $record->check_in_code,
                        auth()->guard(config('filament-cms.guard', 'admin'))->user(),
                    );
                }),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => OrderResource\Pages\ListOrders::route('/')];
    }
}
