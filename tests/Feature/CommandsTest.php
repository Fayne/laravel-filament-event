<?php

use Illuminate\Support\Facades\Artisan;

it('can run package initialize command', function () {
    Artisan::call('cms:event:init');

    $this->assertStringContainsString('Seeding database', Artisan::output());
});
