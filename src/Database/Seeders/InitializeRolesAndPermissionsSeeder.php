<?php

namespace Fayne\Event\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class InitializeRolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        \Fayne\CMS\Support\Authorization\PermissionCatalog::seed();
    }
}
