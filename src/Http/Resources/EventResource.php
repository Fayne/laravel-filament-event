<?php

namespace Fayne\Event\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Fayne\Event\Support\EventLocale;

class EventResource extends JsonResource
{
    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $translated = $this->resource;

        if (EventLocale::isEnabled() && EventLocale::effectiveLocale($request) !== EventLocale::defaultLocale()) {
            $locale = EventLocale::effectiveLocale($request);
            $text = $this->resource->localizedTexts()->where('locale', $locale)->first();
            if ($text) {
                $translated = $text->is_active ? $text : $this->resource;
            } elseif (! $request->attributes->has('event_content_locale')) {
                $legacy = $this->resource->getTranslation($locale);
                $translated = ($legacy?->status ? $legacy : null) ?? $this->resource;
            }
        }

        return [
            $this->mergeWhen($request->attributes->has('event_content_locale'), ['language' => [
                'requested' => EventLocale::effectiveLocale($request),
                'actual' => $translated->locale,
                'fallback' => EventLocale::effectiveLocale($request) !== $translated->locale,
            ]]),
            'id' => $this->public_id,
            'locale' => EventLocale::isEnabled() ? ($translated->locale ?? null) : null,
            'title' => $translated->title,
            'slug' => $translated->slug,
            'subtitle' => $translated->subtitle,
            'description' => $translated->description,
            'shown_in_homepage' => (bool) $this->shown_in_homepage,
            'is_bookable' => (bool) $this->is_bookable,
            'is_charged' => (bool) $this->is_charged,
            'booking_auth_mode' => $this->booking_auth_mode,
            'stock_type' => (int) $this->stock_type->value,
            'currency' => $this->currency,
            'price' => $this->price,
            'start_datetime' => $this->start_datetime,
            'end_datetime' => $this->end_datetime,
            'need_verify' => (bool) $this->need_verify,
            'stock' => $this->stock,
            'category' => new EventCategoryResource($this->whenLoaded('category')),
            'stores' => $this->whenLoaded('stores', function () {
                return $this->stores->map(function ($store) {
                    return [
                        'id' => $store->id,
                        'name' => $store->name ?? null,
                        'stock' => $store->pivot->stock ?? null,
                    ];
                })->values();
            }),
            'timeslots' => $this->whenLoaded('timeslots', function () {
                return $this->timeslots->map(function ($timeslot) {
                    return [
                        'id' => $timeslot->id,
                        'store_id' => $timeslot->store_id,
                        'date' => $timeslot->date,
                        'start_time' => $timeslot->start_time,
                        'end_time' => $timeslot->end_time,
                        'stock' => $timeslot->stock,
                    ];
                })->values();
            }),
        ];
    }
}
