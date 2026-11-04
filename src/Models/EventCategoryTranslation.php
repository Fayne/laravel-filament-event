<?php

namespace Fayne\Event\Models;

use Illuminate\Database\Eloquent\Model;

class EventCategoryTranslation extends Model
{
    use \Fayne\CMS\Support\Concerns\BelongsToTenant;
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];
    public function getTable(): string
    {
        return config('filament-event.tables.event_category_translations', 'event_category_translations');
    }
}
