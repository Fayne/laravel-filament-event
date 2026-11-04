<?php

namespace Tests;

use Fayne\CMS\CMSServiceProvider;
use Fayne\CMS\Filament\CmsPlugin;
use Fayne\Event\EventServiceProvider;
use Fayne\Event\Filament\EventPlugin;
use Fayne\StoreLocator\Filament\StoreLocatorPlugin;
use Fayne\StoreLocator\StoreLocatorServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Panel;
use Filament\PanelProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Permission\PermissionServiceProvider;
use App\Models\User;
use Fayne\CMS\Http\Middleware\OptionalSanctum;
use Fayne\CMS\Models\Admin;

class TestCase extends Orchestra
{
    protected $enablesPackageDiscoveries = true;

    protected function getPackageProviders($app): array
    {
        return [
            FilamentServiceProvider::class,
            CMSServiceProvider::class,
            StoreLocatorServiceProvider::class,
            EventServiceProvider::class,
            PermissionServiceProvider::class,
            FilamentTestPanelProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:5qJq8O4L7PJkq4+9eX6xqz5VxA8w3iH+0mZkR4j7r7U=');
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]);
        $app['config']->set('auth.defaults.guard', 'admin');
        $app['config']->set('auth.guards.admin', ['driver' => 'session', 'provider' => 'admins']);
        $app['config']->set('auth.providers.admins', ['driver' => 'eloquent', 'model' => Admin::class]);
        $app['config']->set('filament-cms.multilingual_enabled', true);
        $app['config']->set('filament-cms.default_locale', 'en');
        $app['config']->set('filament-cms.supported_locales', ['en' => 'English', 'fr' => 'French']);
        $app['config']->set('filament-event.models.user', User::class);
        $app['config']->set('filament-event.alternative_auth_middleware', [OptionalSanctum::class . ':sanctum']);
        $app['config']->set('permission.testing', true);
    }
}

class FilamentTestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel->default()->id('admin')->path('admin')->authGuard('admin')->login()->plugins([
            CmsPlugin::make(),
            StoreLocatorPlugin::make(),
            EventPlugin::make(),
        ]);
    }
}
