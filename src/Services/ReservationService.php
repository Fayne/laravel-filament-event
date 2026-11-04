<?php

namespace Fayne\Event\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Fayne\Event\Enums\EventStockType;
use Fayne\Event\Enums\OrderStatus;
use Fayne\Event\Exceptions\InsufficientStockException;
use Fayne\Event\Models\Event;
use Fayne\Event\Models\EventTimeslot;
use Fayne\Event\Models\Order;
use Fayne\CMS\Models\Admin;
use Fayne\Event\Support\EventConfig;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

class ReservationService
{
    public function reserve(Event $event, ?Authenticatable $user, array $payload): Order
    {
        return DB::transaction(function () use ($event, $user, $payload) {
            $event->refresh();

            if (! $event->status || ! $event->is_bookable) {
                throw new \DomainException('Event is not bookable');
            }

            $quantity = (int) ($payload['quantity'] ?? 1);
            if ($quantity <= 0) {
                throw new \InvalidArgumentException('Quantity must be greater than 0');
            }

            $storeId = $payload['store_id'] ?? null;
            $timeslotId = $payload['timeslot_id'] ?? null;

            $timeslot = null;
            if ($event->stock_type === EventStockType::Timeslot) {
                $timeslot = EventTimeslot::query()
                    ->whereKey($timeslotId)
                    ->where('event_id', $event->id)
                    ->firstOrFail();

                $storeId = $timeslot->store_id;
            }

            if ($event->stock_type === EventStockType::Store) {
                if (! $storeId) {
                    throw new \InvalidArgumentException('store_id is required for store stock type');
                }

                $this->deductStoreStock($event, (int) $storeId, $quantity);
            }

            if ($event->stock_type === EventStockType::Timeslot) {
                $this->deductTimeslotStock($timeslot, $quantity);
            }

            if ($event->stock_type === EventStockType::Event) {
                $this->deductEventStock($event, $quantity);
            }

            $order = new Order;
            $order->event_id = $event->id;
            $order->user_id = $user?->getAuthIdentifier();
            $order->store_id = $storeId;
            $order->timeslot_id = $timeslotId;
            $order->out_trade_no = $this->generateOutTradeNo();
            $order->status = OrderStatus::Reserved;
            $order->quantity = $quantity;

            $order->first_name = $payload['first_name'] ?? null;
            $order->last_name = $payload['last_name'] ?? null;
            $order->gender = $payload['gender'] ?? null;
            $order->area_code = $payload['area_code'] ?? null;
            $order->phone_number = $payload['phone_number'] ?? null;

            $order->check_in_code = $this->generateCheckInCode();

            $order->save();

            return $order->refresh();
        });
    }

    public function cancel(Order $order, ?Authenticatable $actor = null): Order
    {
        return DB::transaction(function () use ($order, $actor) {
            $order = Order::query()->lockForUpdate()->findOrFail($order->getKey());

            if ($actor && (string) $order->user_id !== (string) $actor->getAuthIdentifier()) {
                throw new \DomainException('Unauthorized');
            }

            if ($order->status === OrderStatus::Cancelled) {
                return $order;
            }

            if ($order->status === OrderStatus::CheckedIn) {
                throw new \DomainException('Checked-in order cannot be cancelled');
            }

            $event = $order->event()->firstOrFail();

            $quantity = (int) $order->quantity;

            if ($event->stock_type === EventStockType::Store && $order->store_id) {
                $this->restoreStoreStock($event, (int) $order->store_id, $quantity);
            }

            if ($event->stock_type === EventStockType::Timeslot && $order->timeslot_id) {
                $timeslot = EventTimeslot::query()->whereKey($order->timeslot_id)->first();
                if ($timeslot) {
                    $this->restoreTimeslotStock($timeslot, $quantity);
                }
            }

            if ($event->stock_type === EventStockType::Event) {
                $this->restoreEventStock($event, $quantity);
            }

            $order->status = OrderStatus::Cancelled;
            $order->cancelled_at = now();
            $order->save();

            return $order->refresh();
        });
    }

    public function checkInByCode(string $checkInCode, Authenticatable $actor): Order
    {
        return DB::transaction(function () use ($checkInCode, $actor) {
            $order = Order::query()
                ->where('check_in_code', $checkInCode)
                ->lockForUpdate()
                ->firstOrFail();

            $userModel = EventConfig::getUserModel();
            $adminModel = config('filament-event.admin_model', Admin::class);
            $allowed = $actor instanceof $adminModel
                ? Gate::forUser($actor)->allows('update', $order)
                : $actor instanceof $userModel && (string) $order->user_id === (string) $actor->getAuthIdentifier();
            if (! $allowed) {
                throw new AuthorizationException;
            }

            if ($order->status === OrderStatus::Cancelled) {
                throw new \DomainException('Cancelled order cannot be checked in');
            }

            if ($order->check_in_at) {
                return $order;
            }

            $event = $order->event()->firstOrFail();
            if (! $event->need_verify) {
                throw new \DomainException('Event does not require verification');
            }

            $order->status = OrderStatus::CheckedIn;
            $order->check_in_at = now();
            $order->save();

            return $order->refresh();
        });
    }

    protected function deductEventStock(Event $event, int $quantity): void
    {
        if ($event->stock === null) {
            return;
        }

        $updated = Event::query()
            ->whereKey($event->id)
            ->whereNotNull('stock')
            ->where('stock', '>=', $quantity)
            ->update([
                'stock' => DB::raw('stock - ' . $quantity),
            ]);

        if ($updated === 0) {
            throw new InsufficientStockException('Insufficient event stock');
        }
    }

    protected function restoreEventStock(Event $event, int $quantity): void
    {
        if ($event->stock === null) {
            return;
        }

        Event::query()
            ->whereKey($event->id)
            ->whereNotNull('stock')
            ->update([
                'stock' => DB::raw('stock + ' . $quantity),
            ]);
    }

    protected function deductStoreStock(Event $event, int $storeId, int $quantity): void
    {
        if (! $event->stores()->whereKey($storeId)->exists()) {
            throw new \DomainException('Store not associated with event');
        }

        $pivot = DB::table('event_store')
            ->where('event_id', $event->id)
            ->where('store_id', $storeId)
            ->first();

        if (! $pivot) {
            throw new \DomainException('Store not associated with event');
        }

        if ($pivot->stock === null) {
            return;
        }

        $updated = DB::table('event_store')
            ->where('event_id', $event->id)
            ->where('store_id', $storeId)
            ->whereNotNull('stock')
            ->where('stock', '>=', $quantity)
            ->update([
                'stock' => DB::raw('stock - ' . $quantity),
            ]);

        if ($updated === 0) {
            throw new InsufficientStockException('Insufficient store stock');
        }
    }

    protected function restoreStoreStock(Event $event, int $storeId, int $quantity): void
    {
        DB::table('event_store')
            ->where('event_id', $event->id)
            ->where('store_id', $storeId)
            ->whereNotNull('stock')
            ->update([
                'stock' => DB::raw('stock + ' . $quantity),
            ]);
    }

    protected function deductTimeslotStock(?EventTimeslot $timeslot, int $quantity): void
    {
        if (! $timeslot) {
            throw new \InvalidArgumentException('timeslot is required for timeslot stock type');
        }

        if ($timeslot->stock === null) {
            return;
        }

        $timeslot->deduct($quantity);
    }

    protected function restoreTimeslotStock(EventTimeslot $timeslot, int $quantity): void
    {
        if ($timeslot->stock === null) {
            return;
        }

        EventTimeslot::query()
            ->whereKey($timeslot->id)
            ->whereNotNull('stock')
            ->update([
                'stock' => DB::raw('stock + ' . $quantity),
            ]);
    }

    protected function generateOutTradeNo(): string
    {
        return Str::uuid()->toString();
    }

    protected function generateCheckInCode(): string
    {
        do {
            $code = Str::upper(Str::random(10));
        } while (Order::query()->where('check_in_code', $code)->exists());

        return $code;
    }
}
