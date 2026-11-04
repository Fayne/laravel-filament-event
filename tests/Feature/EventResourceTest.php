<?php

use Fayne\CMS\Models\Admin;
use Fayne\Event\Models\Event;
use Fayne\Event\Models\EventRule;
use Spatie\Permission\Models\Permission;
use Tests\Support\PackageDatabase;
use Fayne\Event\Filament\Resources\EventResource\Pages\EditEvent;

beforeEach(function () {
    config(['logging.default' => 'single']);
    PackageDatabase::prepare();
});

it('blocks Event pages without their resource permission', function () {
    $admin = Admin::query()->create(['name' => 'Event Admin', 'email' => 'event-admin@example.test', 'password' => 'password']);
    $this->actingAs($admin, 'admin')->get('/admin/events')->assertForbidden();
});

it('allows explicitly permitted Event pages and keeps EventRule write routes closed', function () {
    $admin = Admin::query()->create(['name' => 'Event Admin', 'email' => 'event-admin@example.test', 'password' => 'password']);
    $view = Permission::query()->create(['name' => 'tenant.viewAnyEvent', 'guard_name' => 'admin']);
    $create = Permission::query()->create(['name' => 'tenant.createEvent', 'guard_name' => 'admin']);
    $ruleView = Permission::query()->create(['name' => 'tenant.viewAnyEventRule', 'guard_name' => 'admin']);
    $admin->givePermissionTo([$view, $create, $ruleView]);
    $this->actingAs($admin->fresh(), 'admin')->get('/admin/events/create')->assertOk();

    $this->actingAs($admin->fresh(), 'admin')->get('/admin/event-rules')->assertOk();
    $this->get('/admin/event-rules/create')->assertNotFound();
});

it('preserves stored EventRule conditions when editing event fields', function () {
    $event = Event::factory()->create();
    $rule = EventRule::query()->create([
        'event_id' => $event->id,
        'name' => 'Existing rule',
        'conditions' => ['conditions' => [['field' => 'email', 'operator' => 'contains', 'value' => '@example.test']]],
    ]);

    $event->update(['title' => 'Updated event title']);

    expect($rule->fresh()->conditions)->toBe(['conditions' => [['field' => 'email', 'operator' => 'contains', 'value' => '@example.test']]]);
});

it('does not persist forged rule conditions submitted through the event form', function () {
    $admin = Admin::create(['name' => 'Editor', 'email' => 'rule-editor@example.test', 'password' => 'password']);
    $admin->givePermissionTo(Permission::create(['name' => 'tenant.updateEvent', 'guard_name' => 'admin']));
    $admin->givePermissionTo(Permission::create(['name' => 'tenant.viewAnyEvent', 'guard_name' => 'admin']));
    $admin->givePermissionTo(Permission::create(['name' => 'tenant.viewEvent', 'guard_name' => 'admin']));
    $this->actingAs($admin->fresh(), 'admin');
    $event = Event::factory()->create();
    $original = ['conditions' => [['field' => 'email', 'value' => '@example.test']]];
    $rule = EventRule::create(['event_id' => $event->id, 'name' => 'Protected', 'conditions' => $original]);

    Livewire\Livewire::test(EditEvent::class, ['record' => $event->id])
        ->assertOk()
        ->set('data.conditions', ['forged' => true])
        ->set('data.rules', [['id' => $rule->id, 'conditions' => ['forged' => true]]])
        ->fillForm(['title' => 'Edited normally'])
        ->call('save')->assertHasNoFormErrors();

    expect($event->fresh()->title)->toBe('Edited normally')
        ->and($rule->fresh()->conditions)->toBe($original);
});

it('renders order management only for an admin with order view permission', function () {
    $admin = Admin::query()->create(['name' => 'Order Admin', 'email' => 'order-admin@example.test', 'password' => 'password']);
    $view = Permission::query()->create(['name' => 'tenant.viewAnyEventOrder', 'guard_name' => 'admin']);
    $update = Permission::query()->create(['name' => 'tenant.updateEventOrder', 'guard_name' => 'admin']);
    $admin->givePermissionTo([$view, $update]);

    $this->actingAs($admin->fresh(), 'admin')->get('/admin/orders')->assertOk();
});

it('returns an event translation for a supported locale and falls back to the default locale', function () {
    config([
        'filament-cms.multilingual_enabled' => true,
        'filament-cms.default_locale' => 'en',
        'filament-cms.supported_locales' => ['en' => 'English', 'fr' => 'French'],
    ]);

    $event = Event::factory()->create(['title' => 'English title', 'internal_title' => 'EVENT-LOCALE']);
    Event::query()->create([
        'public_id' => $event->public_id,
        'fallback_id' => $event->id,
        'locale' => 'fr',
        'title' => 'Titre français',
        'internal_title' => $event->internal_title,
        'status' => true,
    ]);

    $this->getJson('/modules/events/api/v1/events?locale=fr')
        ->assertOk()
        ->assertJsonPath('data.0.title', 'Titre français')
        ->assertJsonPath('data.0.locale', 'fr');

    $this->getJson('/modules/events/api/v1/events?locale=de')
        ->assertOk()
        ->assertJsonPath('data.0.title', 'English title')
        ->assertJsonPath('data.0.locale', 'en');
});
