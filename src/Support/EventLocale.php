<?php

namespace Fayne\Event\Support;

use Illuminate\Http\Request;

class EventLocale
{
    /**
     * @return bool
     */
    public static function isEnabled(): bool
    {
        if (function_exists('cmsMultilingualEnabled')) {
            return cmsMultilingualEnabled();
        }

        $supported = \Fayne\CMS\Support\ContentLocale::supported();

        return \Fayne\CMS\Support\ContentLocale::enabled() &&
            is_array($supported) &&
            count($supported) > 1;
    }

    /**
     * @return string
     */
    public static function defaultLocale(): string
    {
        return \Fayne\CMS\Support\ContentLocale::default();
    }

    /**
     * @return array
     */
    public static function supportedLocaleKeys(): array
    {
        $supported = \Fayne\CMS\Support\ContentLocale::supported();

        if (!is_array($supported)) {
            return [];
        }

        return array_keys($supported);
    }

    /**
     * @param Request $request
     * @return string|null
     */
    public static function resolve(Request $request): ?string
    {
        if ($locale = $request->query('locale')) {
            return static::normalizeToSupported($locale);
        }

        $header = $request->header('accept-language');

        if (!$header) {
            return null;
        }

        $first = explode(',', $header)[0] ?? null;
        $first = $first ? trim(explode(';', $first)[0]) : null;

        return $first ? static::normalizeToSupported($first) : null;
    }

    /**
     * @param Request $request
     * @return string
     */
    public static function effectiveLocale(Request $request): string
    {
        return $request->attributes->get('event_content_locale') ?? static::resolve($request) ?: static::defaultLocale();
    }

    /**
     * @param string $locale
     * @return string|null
     */
    protected static function normalizeToSupported(string $locale): ?string
    {
        $locale = trim($locale);

        if ($locale === '') {
            return null;
        }

        $supported = static::supportedLocaleKeys();

        foreach ($supported as $supportedLocale) {
            if (strtolower($supportedLocale) === strtolower($locale)) {
                return $supportedLocale;
            }
        }

        $short = strtolower(explode('-', $locale)[0] ?? $locale);

        foreach ($supported as $supportedLocale) {
            if (strtolower($supportedLocale) === $short) {
                return $supportedLocale;
            }
        }

        foreach ($supported as $supportedLocale) {
            if (str_starts_with(strtolower($supportedLocale), $short . '-')) {
                return $supportedLocale;
            }
        }

        return null;
    }
}
