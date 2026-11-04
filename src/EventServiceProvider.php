<?php

namespace Fayne\Event;

use Fayne\Event\Models\BatchInsertEventSlotRecord;
use Fayne\Event\Observers\BatchInsertEventSlotRecordObserver;
use Fayne\Event\Policies\BatchInsertEventSlotRecordPolicy;
use Fayne\Event\Policies\EventCategoryPolicy;
use Fayne\Event\Policies\EventPolicy;
use Fayne\Event\Policies\EventRulePolicy;
use Fayne\Event\Policies\EventTimeslotPolicy;
use Fayne\Event\Policies\OrderPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Fayne\Event\Console\InitializeCommand;
use Fayne\Event\Http\Controllers\Api\V1\EventCategoryController;
use Fayne\Event\Http\Controllers\Api\V1\EventController;
use Fayne\Event\Http\Middleware\ResolveContentLocale;

class EventServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->callAfterResolving(\Fayne\CMS\Support\Authorization\PermissionRegistry::class, function ($registry): void {
            $registry->register('event', require __DIR__ . '/../resources/permissions.php');
        });
        $this->mergeConfigFrom(__DIR__ . '/../config/filament-event.php', 'filament-event');
    }

    public function boot(): void
    {
        $this->publishes([__DIR__ . '/../database/migrations' => database_path('migrations')], ['filament-event-migrations', 'cms-module-assets']);
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'filament-event');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        if ($this->app->runningInConsole()) {
            $this->commands([InitializeCommand::class]);
        }
        $this->publishes([
            __DIR__ . '/../config/filament-event.php' => config_path('filament-event.php'),
        ], ['filament-event-config', 'cms-module-assets']);

        if (config('filament-event.api.enabled', true)) {
            Route::middleware(config('filament-event.api.middleware.public', ['api']))
                ->prefix(config('filament-event.api.prefix', 'modules/events/api/v1'))
                ->name('mn.modules.event.api.')
                ->group(__DIR__ . '/../routes/api.php');
        }

        if (config('filament-event.api.enabled', true)) {
            Route::middleware(array_merge(config('filament-event.api.middleware.public', ['api']), [ResolveContentLocale::class]))
                ->prefix(config('filament-event.api.v2_prefix', 'modules/events/api/v2'))
                ->name('mn.modules.event.api.v2.')
                ->group(function () {
                    Route::apiResource('events', EventController::class)->only(['index', 'show']);
                    Route::apiResource('event-categories', EventCategoryController::class)->only(['index', 'show']);
                });
        }

        BatchInsertEventSlotRecord::observe(BatchInsertEventSlotRecordObserver::class);
        Gate::policy(config('filament-event.models.event'), EventPolicy::class);
        Gate::policy(config('filament-event.models.event_category'), EventCategoryPolicy::class);
        Gate::policy(config('filament-event.models.event_timeslot'), EventTimeslotPolicy::class);
        Gate::policy(config('filament-event.models.event_rule'), EventRulePolicy::class);
        Gate::policy(config('filament-event.models.order'), OrderPolicy::class);
        Gate::policy(BatchInsertEventSlotRecord::class, BatchInsertEventSlotRecordPolicy::class);
    }
}
