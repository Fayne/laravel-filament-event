<?php

use Fayne\Event\Models\BatchInsertEventSlotRecord;
use Fayne\Event\Models\Event;
use Fayne\StoreLocator\Models\Store;
use Tests\Support\PackageDatabase;

beforeEach(function () {
    config(['logging.default' => 'single']);
    PackageDatabase::prepare();
});

it('generates and updates timeslots from explicit batch details', function () {
    $event = Event::factory()->create();
    $store = Store::query()->create(['name' => 'Batch Store']);

    $batch = BatchInsertEventSlotRecord::query()->create([
        'detail' => [
            'event' => $event->id,
            'store' => [$store->id],
            'week' => ['1', '3'],
            'start_time' => '10:00',
            'end_time' => '11:00',
            'stock' => 8,
            'from-till' => json_encode(['from' => '2026-10-12', 'till' => '2026-10-14'], JSON_THROW_ON_ERROR),
        ],
    ]);

    expect($batch->records()->orderBy('date')->pluck('date')->map->toDateString()->all())
        ->toBe(['2026-10-12', '2026-10-14']);

    $batch->update(['detail' => array_replace($batch->detail, ['end_time' => '12:00', 'stock' => 5])]);

    expect($batch->records()->count())->toBe(2)
        ->and($batch->records()->first()->end_time)->toBe('12:00')
        ->and($batch->records()->first()->stock)->toBe(5);
});
