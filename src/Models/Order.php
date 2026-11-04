<?php

namespace Fayne\Event\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Fayne\Event\Database\Factories\OrderFactory;
use Fayne\Event\Enums\OrderStatus;
use Fayne\StoreLocator\Models\Store;

class Order extends Model
{
    use \Fayne\CMS\Support\Concerns\BelongsToTenant;
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'event_orders';

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'status' => OrderStatus::class,
        'metadata' => 'array',
        'notes' => 'array',
        'customer_note' => 'array',
        'paid_at' => 'datetime',
        'first_name' => 'encrypted',
        'last_name' => 'encrypted',
        'gender' => 'encrypted',
        'phone_number' => 'encrypted',
        'check_in_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * @return BelongsTo
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(config('filament-event.models.event'));
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('filament-event.models.user'));
    }

    /**
     * @return BelongsTo
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(config('filament-event.models.store'));
    }

    /**
     * @return BelongsTo
     */
    public function timeslot(): BelongsTo
    {
        return $this->belongsTo(config('filament-event.models.event_timeslot'));
    }

    /**
     * Method scopeEvent
     *
     * @param  Builder  $query  [explicite description]
     * @param  Event  $event  [explicite description]
     */
    public function scopeFilterEvent(Builder $query, Event $event): Builder
    {
        return $query->where('event_id', $event->id);
    }

    /**
     * Method scopeEvent
     *
     * @param  Builder  $query  [explicite description]
     * @param  EventTimeslot  $eventTimeSlot  [explicite description]
     */
    public function scopeFilterEventTimeSlot(Builder $query, EventTimeslot $eventTimeSlot): Builder
    {
        return $query->where('timeslot_id', $eventTimeSlot->id);
    }

    /**
     * Method scopeEvent
     *
     * @param  Builder  $query  [explicite description]
     */
    public function scopeFilterStore(Builder $query, Store $store): Builder
    {
        return $query->where('store_id', $store->id);
    }

    /**
     * Method scopeFilterReserved
     *
     * @param  Builder  $query  [explicite description]
     * @param  array  $status  [explicite description]
     */
    public function scopeFilterStatus(Builder $query, array $status = []): Builder
    {
        return $query->where('user_id', auth()->id())
            ->whereIn('status', $status);
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return TFactory|null
     */
    protected static function newFactory(): Factory
    {
        return OrderFactory::new();
    }
}
