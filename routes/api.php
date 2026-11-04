<?php

use Illuminate\Support\Facades\Route;
use Fayne\Event\Http\Controllers\Api\V1\EventCategoryController;
use Fayne\Event\Http\Controllers\Api\V1\EventController;
use Fayne\Event\Http\Controllers\Api\V1\ReservationController;
use Fayne\Event\Models\Event;
use Fayne\Event\Support\EventLocale;
use Fayne\Event\Support\EventConfig;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::bind('event', function (string $value) {
    $query = Event::query()
        ->published()
        ->where('public_id', $value);

    $query->where('locale', EventLocale::defaultLocale())
        ->where(function ($q) {
            $q->whereNull('fallback_id')->orWhereColumn('fallback_id', 'id');
        });

    return $query->firstOrFail();
});

Route::apiResource('events', EventController::class)->only(['index', 'show'])
    ->names(['index' => 'events.index', 'show' => 'events.show'])
    ->scoped(['event' => 'public_id']);

Route::apiResource('event-categories', EventCategoryController::class)->only(['index', 'show'])
    ->names(['index' => 'event-categories.index', 'show' => 'event-categories.show']);

Route::middleware(config('filament-event.alternative_auth_middleware', []))->group(function () {
    Route::post('events/{event:public_id}/reservations', [ReservationController::class, 'store'])->name('events.reservations');
});

Route::middleware(EventConfig::getAuthApiMiddleware())->group(function () {
    Route::get('me/reservations', [ReservationController::class, 'meIndex'])->name('me.reservations.index');
    Route::get('me/reservations/{order}', [ReservationController::class, 'meShow'])->name('me.reservations.show');
    Route::post('me/reservations/{order}/cancel', [ReservationController::class, 'cancel'])->name('me.reservations.cancel');

    Route::post('reservations/check-in', [ReservationController::class, 'checkIn'])->name('reservations.check-in');
});
