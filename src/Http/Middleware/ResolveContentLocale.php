<?php

namespace Fayne\Event\Http\Middleware;

use Closure;
use Fayne\CMS\Support\ContentLocale;
use Illuminate\Http\Request;

class ResolveContentLocale
{
    public function handle(Request $request, Closure $next)
    {
        $request->validate(['locale' => ['sometimes', 'string']]);
        $request->attributes->set('event_content_locale', ContentLocale::resolve($request->query('locale')));

        return $next($request);
    }
}
