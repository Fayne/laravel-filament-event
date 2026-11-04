<?php

use Fayne\StoreLocator\StoreLocatorServiceProvider;

$eventPackagePath = dirname(__DIR__, 2);
$storePackagePath = dirname((new ReflectionClass(StoreLocatorServiceProvider::class))->getFileName(), 2);

it('runs the store migration before migrations that reference stores', function () use ($eventPackagePath, $storePackagePath) {
    $migrationFiles = array_merge(
        glob($storePackagePath . '/database/migrations/*.php') ?: [],
        glob($eventPackagePath . '/database/migrations/*.php') ?: [],
    );

    usort($migrationFiles, fn (string $left, string $right): int => basename($left) <=> basename($right));

    $migrationNames = array_map('basename', $migrationFiles);

    expect(array_search('2026_10_14_000001_create_stores_table.php', $migrationNames, true))
        ->toBeLessThan(array_search('2026_10_14_000002_create_events_table.php', $migrationNames, true));
});
