<?php

namespace Fayne\Event\Services;

use Fayne\CMS\Support\ContentLocale;
use Fayne\Event\Models\Event;
use Fayne\Event\Models\EventCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class EventTranslationService
{
    public function save(Event|EventCategory $owner, string $locale, array $data): Model
    {
        Gate::forUser(auth(config('filament-cms.guard', 'admin'))->user())->authorize('update', $owner);
        $locale = ContentLocale::resolve($locale);
        if ($owner->locale !== ContentLocale::default() || ($owner->fallback_id !== null && (string) $owner->fallback_id !== (string) $owner->getKey()) || $locale === ContentLocale::default()) {
            throw ValidationException::withMessages(['locale' => __('filament-event::multilingual.canonical_required')]);
        }
        $rules = ['title' => ['required', 'string', 'max:255'], 'subtitle' => ['nullable', 'string', 'max:255'], 'description' => ['nullable', 'string'], 'is_active' => ['sometimes', 'boolean']];
        if ($owner instanceof Event) {
            $rules['slug'] = ['nullable', 'string', 'max:255'];
        }
        $values = Validator::make(Arr::only($data, array_keys($rules)), $rules)->validate();

        return $owner->localizedTexts()->updateOrCreate(['locale' => $locale], $values);
    }
}
