<?php

namespace Fayne\Event\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Fayne\Event\Http\Resources\EventResource;
use Fayne\Event\Models\Event;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Fayne\Event\Support\EventLocale;

class EventController extends Controller
{
    /**
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $query = Event::query()
            ->published()
            ->where('locale', EventLocale::defaultLocale())
            ->where(function ($query) {
                $query->whereNull('fallback_id')->orWhereColumn('fallback_id', 'id');
            })
            ->when($request->filled('category_id'), function ($query) use ($request) {
                $query->where('event_category_id', $request->integer('category_id'));
            })
            ->when($request->filled('shown_in_homepage'), function ($query) use ($request) {
                $query->where('shown_in_homepage', (bool) $request->input('shown_in_homepage'));
            })
            ->when($request->filled('is_bookable'), function ($query) use ($request) {
                $query->where('is_bookable', (bool) $request->input('is_bookable'));
            })
            ->when($request->filled('need_verify'), function ($query) use ($request) {
                $query->where('need_verify', (bool) $request->input('need_verify'));
            })
            ->with(['category'])
            ->orderBy('weight');

        return EventResource::collection(
            $query->paginate((int) $request->input('per_page', 15))
        );
    }

    /**
     * @param Request $request
     * @param Event $event
     * @return EventResource
     */
    public function show(Request $request, Event $event)
    {
        $event->load(['category', 'stores', 'timeslots']);

        return new EventResource($event);
    }
}
