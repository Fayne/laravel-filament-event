<?php

use Fayne\Event\Models\Event;
use Fayne\Event\Models\EventTranslation;
use Fayne\Event\Services\EventTranslationService;
use Illuminate\Support\Facades\Gate;
use Tests\Support\PackageDatabase;
use Fayne\CMS\Models\Admin;
use Fayne\Event\Filament\Resources\EventResource\Pages\EditEvent;
use Fayne\Event\Models\EventCategory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Fayne\Event\Filament\Resources\EventResource\Pages\CreateEvent;

beforeEach(function () {
    PackageDatabase::prepare();
    config(['filament-cms.multilingual_enabled' => true, 'filament-cms.default_locale' => 'en', 'filament-cms.supported_locales' => ['en' => 'English', 'fr' => 'French']]);
});

it('hides unpublished legacy wording and filters disabled multilingual roots', function () {
    $event = Event::factory()->create(['title' => 'Public wording']);
    Event::factory()->create(['public_id' => $event->public_id, 'internal_title' => $event->internal_title, 'locale' => 'fr', 'fallback_id' => $event->id, 'title' => 'Private wording', 'status' => false]);
    $this->getJson('/modules/events/api/v1/events?locale=fr')->assertJsonPath('data.0.title', 'Public wording');
    config(['filament-cms.multilingual_enabled' => false]);
    $this->getJson('/modules/events/api/v1/events')->assertJsonCount(1, 'data')->assertJsonPath('data.0.locale', null);
});

it('reads active wording with canonical identity and never leaks inactive wording through legacy fallback', function () {
    $event = Event::factory()->create(['stock' => 9]);
    Event::factory()->create(['public_id' => $event->public_id, 'internal_title' => $event->internal_title, 'locale' => 'fr', 'fallback_id' => $event->id, 'title' => 'Legacy wording']);
    $translation = EventTranslation::create(['event_id' => $event->id, 'locale' => 'fr', 'title' => 'French wording', 'is_active' => true]);
    $this->getJson('/modules/events/api/v1/events?locale=fr')->assertJsonPath('data.0.title', 'French wording')->assertJsonPath('data.0.id', $event->public_id)->assertJsonPath('data.0.stock', 9);
    $translation->update(['is_active' => false]);
    $this->getJson('/modules/events/api/v1/events?locale=fr')->assertJsonPath('data.0.title', $event->title)->assertJsonPath('data.0.locale', 'en');
});

it('requires canonical update authorization for translation writes', function () {
    $event = Event::factory()->create();
    Gate::define('update', fn () => false);
    expect(fn () => app(EventTranslationService::class)->save($event, 'fr', ['title' => 'French']))->toThrow(AuthorizationException::class);
});

it('writes only localized fields while leaving business facts unchanged', function () {
    $event = Event::factory()->create(['stock' => 7]);
    $admin = Admin::create(['is_active' => true, 'name' => 'Translator', 'email' => 'translator@example.test', 'password' => 'password']);
    $admin->givePermissionTo(Permission::create(['name' => 'tenant.updateEvent', 'guard_name' => 'admin']));
    $this->actingAs($admin, 'admin');
    config(['auth.defaults.guard' => 'web']);
    $translation = app(EventTranslationService::class)->save($event, 'FR', ['title' => 'French', 'stock' => 0, 'is_active' => true]);
    expect($translation->locale)->toBe('fr')->and($event->fresh()->stock)->toBe(7)->and($event->fresh()->public_id)->toBe($event->public_id);
});

it('validates strict v2 locale and reports the actual fallback language', function () {
    Event::factory()->create();
    $this->getJson('/modules/events/api/v2/events?locale=de')->assertUnprocessable();
    $this->getJson('/modules/events/api/v2/events?locale=fr')->assertOk()->assertJsonPath('data.0.language.requested', 'fr')->assertJsonPath('data.0.language.actual', 'en')->assertJsonPath('data.0.language.fallback', true);
});

it('lets authorized editors save wording from the event translation action', function () {
    $admin = Admin::create(['is_active' => true, 'name' => 'Translator', 'email' => 'action@example.test', 'password' => 'password']);
    foreach (['tenant.updateEvent', 'tenant.viewEvent', 'tenant.viewAnyEvent'] as $permission) {
        $admin->givePermissionTo(Permission::create(['name' => $permission, 'guard_name' => 'admin']));
    }
    $this->actingAs($admin, 'admin');
    $event = Event::factory()->create();
    Livewire\Livewire::test(EditEvent::class, ['record' => $event->id])
        ->callAction('translate', data: ['locale' => 'fr', 'title' => 'French action', 'is_active' => true])
        ->assertHasNoActionErrors();
    expect($event->localizedTexts()->where('locale', 'fr')->value('title'))->toBe('French action');
});

it('keeps disabled listings canonical even when legacy translations were published', function () {
    $event = Event::factory()->create();
    Event::factory()->create(['public_id' => $event->public_id, 'internal_title' => $event->internal_title, 'locale' => 'fr', 'fallback_id' => $event->id]);
    config(['filament-cms.multilingual_enabled' => false]);
    $this->getJson('/modules/events/api/v1/events')->assertJsonCount(1, 'data');
    $this->getJson('/modules/events/api/v2/events?locale=fr')->assertUnprocessable();
});

it('uses default locale in v2 regardless of accept-language and rejects array locale', function () {
    Event::factory()->create();
    $this->withHeader('Accept-Language', 'fr')->getJson('/modules/events/api/v2/events')->assertJsonPath('data.0.language.requested', 'en');
    $this->getJson('/modules/events/api/v2/events?locale[]=fr')->assertUnprocessable();
});

it('enforces one wording record per business entity and locale', function () {
    $event = Event::factory()->create();
    EventTranslation::create(['event_id' => $event->id, 'locale' => 'fr', 'title' => 'First']);
    expect(fn () => EventTranslation::create(['event_id' => $event->id, 'locale' => 'fr', 'title' => 'Second']))->toThrow(QueryException::class);
});

it('keeps category translations on their canonical identity', function () {
    $category = EventCategory::factory()->create();
    $category->localizedTexts()->create(['locale' => 'fr', 'title' => 'French category', 'is_active' => true]);
    $this->getJson('/modules/events/api/v2/event-categories?locale=fr')->assertJsonPath('data.0.id', $category->id)->assertJsonPath('data.0.title', 'French category')->assertJsonPath('data.0.language.actual', 'fr');
});

it('rejects unsupported default-language and legacy-child translation writes', function () {
    $admin = Admin::create(['is_active' => true, 'name' => 'Translator', 'email' => 'reject@example.test', 'password' => 'password']);
    $admin->givePermissionTo(Permission::create(['name' => 'tenant.updateEvent', 'guard_name' => 'admin']));
    $this->actingAs($admin, 'admin');
    $event = Event::factory()->create();
    $service = app(EventTranslationService::class);
    foreach (['de', 'en'] as $locale) {
        expect(fn () => $service->save($event, $locale, ['title' => 'Rejected']))->toThrow(ValidationException::class);
    }
    $child = Event::factory()->create(['locale' => 'fr', 'fallback_id' => $event->id]);
    expect(fn () => $service->save($child, 'fr', ['title' => 'Rejected']))->toThrow(ValidationException::class);
});

it('does not expose legacy category identities through v2 details', function () {
    $root = EventCategory::factory()->create();
    $child = EventCategory::factory()->create(['locale' => 'fr', 'fallback_id' => $root->id]);
    $this->getJson('/modules/events/api/v2/event-categories/' . $child->id)->assertNotFound();
});

it('creates canonical events in the configured non-English default when multilingual is disabled', function () {
    config(['filament-cms.multilingual_enabled' => false, 'filament-cms.default_locale' => 'fr']);
    $admin = Admin::create(['is_active' => true, 'name' => 'Creator', 'email' => 'default@example.test', 'password' => 'password']);
    foreach (['tenant.createEvent', 'tenant.viewAnyEvent'] as $permission) {
        $admin->givePermissionTo(Permission::create(['name' => $permission, 'guard_name' => 'admin']));
    }
    $this->actingAs($admin, 'admin');
    Livewire\Livewire::test(CreateEvent::class)
        ->fillForm(['internal_title' => 'DEFAULT-FR', 'title' => 'French canonical', 'locale' => 'en', 'stock_type' => 0, 'booking_auth_mode' => 'both'])
        ->call('create')->assertHasNoFormErrors();
    expect(Event::where('internal_title', 'DEFAULT-FR')->firstOrFail()->locale)->toBe('fr');
});

it('loads translated event action copy and service errors', function () {
    app()->setLocale('zh_CN');
    expect(__('filament-event::multilingual.edit_translation'))->toBe('编辑翻译');
    app()->setLocale('zh-CN');
    expect(__('filament-event::multilingual.edit_translation'))->toBe('编辑翻译');
});

it('initializes configured default on model creation without rewriting explicit legacy locales', function () {
    config(['filament-cms.multilingual_enabled' => false, 'filament-cms.default_locale' => 'fr']);
    expect(Event::factory()->create()->locale)->toBe('fr');
    expect(EventCategory::factory()->create()->locale)->toBe('fr');
    expect(Event::factory()->create(['locale' => 'en'])->locale)->toBe('en');
});

it('preserves enabled legacy category details but rejects nondefault details when multilingual is disabled', function () {
    $root = EventCategory::factory()->create();
    $child = EventCategory::factory()->create(['locale' => 'fr', 'fallback_id' => $root->id, 'title' => 'French category']);
    $this->getJson('/modules/events/api/v1/event-categories/' . $child->id)->assertOk()->assertJsonPath('data.title', 'French category');
    config(['filament-cms.multilingual_enabled' => false]);
    $this->getJson('/modules/events/api/v1/event-categories/' . $child->id)->assertNotFound();
    $this->getJson('/modules/events/api/v1/event-categories/' . $root->id)->assertOk()->assertJsonPath('data.locale', null);
});

it('projects active event wording without changing business identity or facts', function () {
    $event = Event::factory()->create(['stock' => 8, 'price' => 23.50]);
    $event->localizedTexts()->create(['locale' => 'fr', 'title' => 'Projected French', 'slug' => '', 'subtitle' => '', 'description' => null, 'is_active' => true]);
    $projection = $event->publicProjectionForLocale('fr');
    expect($projection['title'])->toBe('Projected French')->and($projection['id'])->toBe($event->id)->and($projection['stock'])->toBe(8)->and($projection['price'])->toBe($event->price)->and($projection['locale'])->toBe('fr')->and($projection['slug'])->toBe('')->and($projection['subtitle'])->toBe('')->and($projection['description'])->toBeNull();
    expect($event->fresh()->title)->toBe($event->title);
});

it('projects canonical event and category wording for inactive or missing translations', function () {
    foreach ([Event::factory()->create(), EventCategory::factory()->create()] as $owner) {
        $owner->localizedTexts()->create(['locale' => 'fr', 'title' => 'Private wording', 'is_active' => false]);
        $projection = $owner->publicProjectionForLocale('fr');
        expect($projection['title'])->toBe($owner->title)->and($projection['locale'])->toBe('en')->and($projection['id'])->toBe($owner->id);
        $owner->localizedTexts()->delete();
        expect($owner->publicProjectionForLocale('fr')['title'])->toBe($owner->title);
    }
});
