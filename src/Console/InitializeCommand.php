<?php

namespace Fayne\Event\Console;

use Fayne\Event\Database\Seeders\DemoSeeder;
use Fayne\Event\Database\Seeders\InitializeRolesAndPermissionsSeeder;
use Illuminate\Console\Command;

class InitializeCommand extends Command
{
    protected $signature = 'cms:event:init {--demo : Seed demo data for local development}';

    protected $description = 'Initialize the event module.';

    public function handle(): int
    {
        $result = $this->call('db:seed', ['--class' => InitializeRolesAndPermissionsSeeder::class]);
        if ($result !== self::SUCCESS) {
            return $result;
        }

        return $this->option('demo')
            ? $this->call('db:seed', ['--class' => DemoSeeder::class])
            : self::SUCCESS;
    }
}
