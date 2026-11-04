<?php

namespace Fayne\Event\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Fayne\CMS\Contracts\Translatable;
use Fayne\CMS\Support\Concerns\HasMultilingual;
use Fayne\Event\Database\Factories\EventFactory;
use Fayne\Event\Enums\EventStockType;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Fayne\CMS\Support\ContentLocale;

class Event extends Model implements Sortable, Translatable
{
    use \Fayne\CMS\Support\Concerns\BelongsToTenant;
    use HasFactory, HasMultilingual, SortableTrait;

    public function publicProjectionForLocale(string $locale): array
    {
        $locale = ContentLocale::resolve($locale);
        $attributes = $this->attributesToArray();
        if ($locale === ContentLocale::default()) {
            return $attributes;
        }

        $translation = $this->localizedTexts()->where('locale', $locale)->where('is_active', true)->first();
        if ($translation) {
            foreach (['title', 'slug', 'subtitle', 'description'] as $field) {
                $attributes[$field] = $translation->getAttribute($field);
            }
            $attributes['locale'] = $translation->locale;
        }

        return $attributes;
    }

    public function localizedTexts(): HasMany
    {
        return $this->hasMany(config('filament-event.models.event_translation'), 'event_id');
    }

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = false;

    /**
     * @var array|string[]
     */
    protected array $translatableFields = [
        'title',
        'slug',
        'subtitle',
        'description',
        'content',
    ];

    /**
     * @var array
     */
    public $sortable = [
        'order_column_name' => 'weight',
        'sort_when_creating' => true,
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'start_datetime' => 'date',
        'end_datetime' => 'date',
        'shown_in_homepage' => 'boolean',
        'is_charged' => 'boolean',
        'is_bookable' => 'boolean',
        'status' => 'boolean',
        'need_verify' => 'boolean',
        'stock_type' => EventStockType::class,
    ];

    /**
     * @return BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class, 'event_category_id');
    }

    /**
     * @return BelongsToMany
     */
    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(config('filament-event.models.store'))
            ->withPivot(['stock'])
            ->withTimestamps();
    }

    /**
     * @return HasMany
     */
    public function timeslots(): HasMany
    {
        return $this->hasMany(config('filament-event.models.event_timeslot'));
    }

    /**
     * @return HasMany
     */
    public function rules(): HasMany
    {
        return $this->hasMany(EventRule::class);
    }

    /**
     * @return HasMany
     */
    public function orders(): HasMany
    {
        return $this->hasMany(config('filament-event.models.order'));
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', true);
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeFilterStoreId(Builder $query): Builder
    {
        return $query->when(request()->has('store_id'), function ($query) {
            $query->whereHas('stores', function ($stores) {
                $stores->where('stores.id', request()->get('store_id'));
            });
        });
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function scopeFilterCategoryId(Builder $query): Builder
    {
        return $query->when(request()->get('category_id'), function () use ($query) {
            $query->where('event_category_id', request()->get('category_id'));
        });
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function scopeFilterShownInHomepage(Builder $query): Builder
    {
        return $query->when(request()->get('shown_in_homepage'), function () use ($query) {
            $query->where('shown_in_homepage', request()->get('shown_in_homepage'));
        });
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function scopeFilterBooked(Builder $query): Builder
    {
        return $query->when(request()->get('is_bookable'), function () use ($query) {
            $query->where('is_bookable', request()->get('is_bookable'));
        });
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function scopeFilterPaid(Builder $query): Builder
    {
        return $query->when(request()->get('is_paid'), function () use ($query) {
            $query->where('is_paid', request()->get('is_paid'));
        });
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function scopeFilterNeedVerify(Builder $query): Builder
    {
        return $query->when(request()->get('need_verify'), function () use ($query) {
            $query->where('need_verify', request()->get('need_verify'));
        });
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return TFactory|null
     */
    protected static function newFactory(): Factory
    {
        return EventFactory::new();
    }

    /**
     * @return bool
     */
    public function hasStock(): bool
    {
        return $this->stock > 0;
    }

    /**
     * @return false|int
     */
    public function decreaseStock(): bool|int
    {
        return $this->hasStock() ? $this->decrement('stock') : false;
    }

    /**
     * Method eventTimeSlotExpired
     */
    public function eventExpired($entity): bool
    {
        return $this->stock_type === EventStockType::Timeslot ?
            $entity->eventExpired() :
            now() > Carbon::parse($this->start_datetime)->subDays(1);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getStockEntity(): Event|Model|EventTimeslot
    {
        if ($this->stock_type === EventStockType::Event) {
            return $this;
        }

        $model = config('filament-event.stock_type')[$this->stock_type->value];

        return $model::find(request()->get('entity_id'))->load('event');

    }

    /**
     * @param $entity
     * @return mixed
     */
    public function getTimeSlotId($entity): mixed
    {
        return $this->stock_type === EventStockType::Timeslot ? $entity->id : null;
    }

    /**
     * @param $entity
     * @return mixed
     */
    public function getStoreId($entity): mixed
    {
        $storeId = $entity->id;
        if ($this->stock_type === EventStockType::Timeslot) {
            $storeId = $entity->store->id;
        }

        return $storeId;
    }

    /**
     * @return array
     */
    public function getDateAndTime($entity)
    {
        $date = date('Y-m-d');
        $startTime = null;
        $endTime = null;
        if ($this->stock_type === EventStockType::Timeslot) {
            $date = $entity->date;
            $startTime = $entity->start_time;
            $endTime = $entity->end_time;
        }

        return [
            'date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ];
    }

    /**
     * Perform any actions required after the model boots.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($model) {
            if (blank($model->locale)) {
                $model->locale = ContentLocale::default();
            }
            if (!$model->slug) {
                $model->slug = Str::slug($model->title);
            }
        });
    }
}
