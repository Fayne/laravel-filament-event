<?php

namespace Fayne\Event\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Fayne\Event\Support\EventLocale;

class EventCategoryResource extends JsonResource
{
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
                $translated = $legacy ?? $this->resource;
            }
        }

        return [
            $this->mergeWhen($request->attributes->has('event_content_locale'), ['language' => [
                'requested' => EventLocale::effectiveLocale($request),
                'actual' => $translated->locale,
                'fallback' => EventLocale::effectiveLocale($request) !== $translated->locale,
            ]]),
            'id' => $this->id,
            'locale' => EventLocale::isEnabled() ? ($translated->locale ?? null) : null,
            'title' => $translated->title,
            'subtitle' => $translated->subtitle,
            'description' => $translated->description,
        ];
    }
}
