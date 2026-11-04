<?php

namespace Fayne\Event\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Fayne\Event\Database\Factories\EventTimeslotFactory;
use Fayne\Event\Exceptions\InsufficientStockException;

class EventTimeslot extends Model
{
    use \Fayne\CMS\Support\Concerns\BelongsToTenant;
    use HasFactory, HasUlids, SoftDeletes;

    protected $table = 'event_timeslots';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'date' => 'date',
    ];

    /**
     * @return BelongsTo
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(config('filament-event.models.store'));
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return TFactory|null
     */
    protected static function newFactory(): Factory
    {
        return EventTimeslotFactory::new();
    }

    /**
     * @return bool
     */
    public function hasStock(): bool
    {
        return $this->stock > 0;
    }

    /**
     * Atomically deduct stock (recommended, lock-free)
     */
    public function deduct(int $quantity): void
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Deduction quantity must be greater than 0');
        }

        $updated = static::where('id', $this->id)
            ->where('stock', '>=', $quantity)
            ->update([
                'stock' => DB::raw("stock - {$quantity}"),
            ]);

        if ($updated === 0) {
            // Re-fetch to provide a more accurate error message
            $current = static::find($this->id);
            throw new InsufficientStockException(
                "Insufficient stock, current stock: {$current->stock}, requested deduction: {$quantity}"
            );
        }

        // Sync the current instance attributes to avoid reading stale data
        $this->refresh();
    }

    /**
     * @return bool
     */
    public function hasExpired(): bool
    {
        return now() > Carbon::parse($this->date . ' ' . $this->start_time)->subDay();
    }
}
