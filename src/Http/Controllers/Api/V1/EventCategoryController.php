<?php

namespace Fayne\Event\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Fayne\Event\Http\Resources\EventCategoryResource;
use Fayne\Event\Models\EventCategory;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Fayne\Event\Support\EventLocale;

class EventCategoryController extends Controller
{
    /**
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $query = EventCategory::query()->orderBy('weight');

        $query->where('locale', EventLocale::defaultLocale())
            ->where(function ($q) {
                $q->whereNull('fallback_id')->orWhereColumn('fallback_id', 'id');
            });

        return EventCategoryResource::collection(
            $query->paginate((int) $request->input('per_page', 50))
        );
    }

    /**
     * @param EventCategory $eventCategory
     * @return EventCategoryResource
     */
    public function show(Request $request, EventCategory $eventCategory)
    {
        if (! EventLocale::isEnabled() || $request->attributes->has('event_content_locale')) {
            abort_unless($eventCategory->locale === EventLocale::defaultLocale() && (blank($eventCategory->fallback_id) || (string) $eventCategory->fallback_id === (string) $eventCategory->id), 404);
        }

        return new EventCategoryResource($eventCategory);
    }
}
