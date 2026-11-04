<p align="center"><img src="https://res.cloudinary.com/fayne/image/upload/v1787643269/fayne_logo.png" width="300"></p>

# Laravel Filament Event

[简体中文](../README.md) | English

For **PHP 8.4+, Laravel 13 and Filament 5**.

## Installation

Configure authorized Composer sources for this private package and its required `fayne/*` dependencies in the host first.

```bash
composer require fayne/laravel-filament-event
php artisan vendor:publish --tag=filament-event-config
php artisan migrate
```

Laravel discovers the service provider automatically. Field-only packages need no Panel plugin. See [composer.json](../composer.json) for exact dependencies; do not copy private credentials from a development host.

## Features, usage and boundaries

Register `Fayne\Event\Filament\EventPlugin::make()` on the panel. Configure `models.*`, `admin_model`, API middleware/prefix and booking fields in `config/filament-event.php`. Complete CMS authentication and Store Locator setup first.

Provides event, category, rule, timeslot and reservation order resources, guest/authenticated booking, event/store/timeslot stock and check-in. Run `php artisan cms:install` to collect registered permissions; explicitly assign new permissions to existing roles. `cms:event:init --demo` appends local demo data. Reservation permissions use `tenant.*EventOrder`, independently of Commerce.

Default API prefix: `/modules/events/api/v1`.

| Method | Path | Purpose |
| --- | --- | --- |
| GET | `/events`, `/events/{event}` | Browse events |
| GET | `/event-categories`, `/event-categories/{eventCategory}` | Browse categories |
| POST | `/events/{event}/reservations` | Guest or authenticated booking |
| GET | `/me/reservations`, `/me/reservations/{order}` | Own reservations |
| POST | `/me/reservations/{order}/cancel` | Cancel own reservation |
| POST | `/reservations/check-in` | Check in |

Configure Sanctum and the authentication middleware for private endpoints. Read-only event/category v2 endpoints default to `/modules/events/api/v2` and resolve content language independently of UI language, following CMS Core settings. Validate database stock concurrency, authorization and external QR services in the target environment.

## Testing and deployment

Run `composer install`, `composer test`, and `composer format` from the package directory. Automated checks do not replace host UI and target-environment acceptance.

## Cooperation and contributions

Open an issue with reproduction steps, versions and expected behavior. Include relevant checks and outstanding environment acceptance in pull requests. Never submit credentials or sensitive business data. For commercial cooperation and licensing, contact [Fayne Guo](mailto:fayne.guo@gmail.com).

## License

[Proprietary license](../LICENSE.md). Copying, distribution and use require authorization. Third-party dependencies retain their own licenses.

## Development and security

[Development and CI](../CONTRIBUTING.md) · [Security reports](../SECURITY.md). Run `composer check` for syntax, formatting and available standalone tests. Do not commit `composer.lock`.
