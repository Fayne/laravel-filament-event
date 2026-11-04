<?php

use Fayne\Event\Models\Event;
use Fayne\Event\Models\EventCategory;

beforeEach(function () {
    config(['filament-cms.supported_locales' => ['en' => 'English', 'zh-CN' => '简体中文']]);
});

it('returns translated event fields by locale query param with default fallback', function () {
    $category = EventCategory::factory()->create([
        'title' => 'Category EN',
    ]);

    $event = Event::factory()->create([
        'event_category_id' => $category->id,
        'title' => 'Event EN',
        'slug' => 'event-en',
        'description' => 'Desc EN',
    ]);

    Event::factory()->create([
        'public_id' => $event->public_id,
        'internal_title' => $event->internal_title,
        'event_category_id' => $category->id,
        'locale' => 'zh-CN',
        'fallback_id' => $event->id,
        'title' => 'Event ZH',
        'slug' => 'event-zh',
        'description' => 'Desc ZH',
    ]);

    $this->getJson('/modules/events/api/v1/events?locale=zh-CN')
        ->assertOk()
        ->assertJsonPath('data.0.title', 'Event ZH')
        ->assertJsonPath('data.0.slug', 'event-zh')
        ->assertJsonPath('data.0.locale', 'zh-CN');

    $this->getJson('/modules/events/api/v1/events?locale=fr')
        ->assertOk()
        ->assertJsonPath('data.0.title', 'Event EN')
        ->assertJsonPath('data.0.slug', 'event-en')
        ->assertJsonPath('data.0.locale', 'en');
});

it('returns translated event fields by accept-language header', function () {
    $event = Event::factory()->create([
        'title' => 'Event EN',
        'slug' => 'event-en',
    ]);

    Event::factory()->create([
        'public_id' => $event->public_id,
        'internal_title' => $event->internal_title,
        'locale' => 'zh-CN',
        'fallback_id' => $event->id,
        'title' => 'Event ZH',
        'slug' => 'event-zh',
    ]);

    $this->withHeader('Accept-Language', 'zh-CN,zh;q=0.9,en;q=0.8')
        ->getJson('/modules/events/api/v1/events')
        ->assertOk()
        ->assertJsonPath('data.0.title', 'Event ZH')
        ->assertJsonPath('data.0.locale', 'zh-CN');
});

it('returns translated category fields while keeping stable id', function () {
    $category = EventCategory::factory()->create([
        'title' => 'Category EN',
        'subtitle' => 'Sub EN',
        'description' => 'Desc EN',
    ]);

    EventCategory::factory()->create([
        'locale' => 'zh-CN',
        'fallback_id' => $category->id,
        'title' => 'Category ZH',
        'subtitle' => 'Sub ZH',
        'description' => 'Desc ZH',
    ]);

    $this->getJson('/modules/events/api/v1/event-categories?locale=zh-CN')
        ->assertOk()
        ->assertJsonPath('data.0.id', $category->id)
        ->assertJsonPath('data.0.title', 'Category ZH')
        ->assertJsonPath('data.0.locale', 'zh-CN');
});
