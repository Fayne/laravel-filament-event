<?php

namespace Fayne\Event\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Fayne\CMS\Contracts\Translatable;
use Fayne\CMS\Support\Concerns\HasMultilingual;
use Fayne\Event\Database\Factories\EventCategoryFactory;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Fayne\CMS\Support\ContentLocale;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventCategory extends Model implements Sortable, Translatable
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
            foreach (['title', 'subtitle', 'description'] as $field) {
                $attributes[$field] = $translation->getAttribute($field);
            }
            $attributes['locale'] = $translation->locale;
        }

        return $attributes;
    }

    public function localizedTexts(): HasMany
    {
        return $this->hasMany(config('filament-event.models.event_category_translation'), 'event_category_id');
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
        'subtitle',
        'description',
    ];

    /**
     * @var array
     */
    public $sortable = [
        'order_column_name' => 'weight',
        'sort_when_creating' => true,
    ];

    /**
     * Create a new factory instance for the model.
     *
     * @return TFactory|null
     */
    protected static function newFactory(): Factory
    {
        return EventCategoryFactory::new();
    }
    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (blank($model->locale)) {
                $model->locale = ContentLocale::default();
            }
        });
    }
}
