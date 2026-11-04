<?php

namespace Tests\Support;

use Spatie\Permission\PermissionRegistrar;
use Fayne\CMS\CMSServiceProvider;
use Fayne\StoreLocator\StoreLocatorServiceProvider;

final class PackageDatabase
{
    public static function prepare(): void
    {
        $package = dirname(__DIR__, 2);
        $core = dirname((new \ReflectionClass(CMSServiceProvider::class))->getFileName(), 2);
        $store = dirname((new \ReflectionClass(StoreLocatorServiceProvider::class))->getFileName(), 2);
        foreach (glob($core . '/database/migrations/*.php') ?: [] as $migration) {
            (require $migration)->up();
        }
        foreach (glob($store . '/database/migrations/*.php') ?: [] as $migration) {
            (require $migration)->up();
        }
        foreach (glob($package . '/tests/database/migrations/0000_00_00_000000_create_users_table.php') ?: [] as $migration) {
            (require $migration)->up();
        }
        foreach (glob($package . '/tests/database/migrations/2024_07_19_223714_create_personal_access_tokens_table.php') ?: [] as $migration) {
            (require $migration)->up();
        }
        foreach (glob($package . '/database/migrations/*.php') ?: [] as $migration) {
            (require $migration)->up();
        }

        app(\Fayne\CMS\Support\Authorization\AuthorizationContext::class)->activate(0);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
