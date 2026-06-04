<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SetLocale
{
    private const CARBON_LOCALES = ['pt' => 'pt_BR', 'en' => 'en', 'es' => 'es'];

    public function handle(Request $request, Closure $next)
    {
        $locale = session('locale', config('app.locale', 'pt'));
        if (in_array($locale, ['pt', 'en', 'es'])) {
            app()->setLocale($locale);
            Carbon::setLocale(self::CARBON_LOCALES[$locale] ?? 'pt_BR');
        }
        return $next($request);
    }
}
