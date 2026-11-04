<?php

namespace Fayne\Event\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Fayne\CMS\Models\Admin;

class BatchInsertEventSlotRecord extends Model
{
    use \Fayne\CMS\Support\Concerns\BelongsToTenant;
    use SoftDeletes;

    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'detail' => 'array',
    ];

    /**
     * @return BelongsTo
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * @return HasMany
     */
    public function records(): HasMany
    {
        return $this->hasMany(EventTimeslot::class, 'batch_id');
    }

    /**
     * @return Attribute
     */
    protected function event(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->detail['event'] ?? ''
        );
    }

    /**
     * @return Attribute
     */
    protected function store(): Attribute
    {
        return Attribute::make(
            get: fn () => collect($this->detail['store'] ?? [])
        );
    }

    /**
     * @return Attribute
     */
    public function fromTill(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->detail) {
                    return [];
                }
                if (Str::isJson($this->detail['from-till'])) {
                    $date = json_decode($this->detail['from-till'], true);
                } else {
                    $date = explode(' - ', $this->detail['from-till'] ?? '');
                }
                $date['from'] = Carbon::make($date['from'] ?? $date[0])->format('Y-m-d');
                $date['till'] = Carbon::make($date['till'] ?? $date[1])->format('Y-m-d');

                return $date;
            }
        );
    }

    /**
     * @return Attribute
     */
    public function from(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->from_till['from'] ?? ''
        );
    }

    /**
     * @return Attribute
     */
    public function till(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->from_till['till'] ?? ''
        );
    }

    /**
     * @return Attribute
     */
    public function week(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->detail['week'] ?? []
        );
    }

    /**
     * @return Attribute
     */
    public function startTime(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->detail['start_time'] ?? ''
        );
    }

    /**
     * @return Attribute
     */
    public function endTime(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->detail['end_time'] ?? ''
        );
    }

    /**
     * @return Attribute
     */
    public function stock(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->detail['stock'] ?? 0
        );
    }
}
