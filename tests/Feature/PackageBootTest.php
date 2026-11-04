<?php

use Fayne\Event\EventServiceProvider;

it('boots the Event package independently with its Filament plugin', function () {
    expect(app()->getLoadedProviders())->toHaveKey(EventServiceProvider::class)
        ->and(Filament\Facades\Filament::getPanel('admin')->getPlugins())->toHaveKey('event');
});
