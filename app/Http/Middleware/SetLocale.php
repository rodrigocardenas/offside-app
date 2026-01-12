<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // 1. Si el usuario está autenticado, usar su idioma preferido
        if (auth()->check() && auth()->user()->language) {
            App::setLocale(auth()->user()->language);
        }
        // 2. Si hay idioma en la sesión, usarlo
        elseif (session()->has('locale')) {
            App::setLocale(session('locale'));
        }
        // 3. Si hay idioma en los parámetros de query, guardarlo
        elseif ($request->has('locale')) {
            $locale = $request->query('locale');
            if (in_array($locale, ['es', 'en'])) {
                session(['locale' => $locale]);
                App::setLocale($locale);
            }
        }
        // 4. Detectar idioma del navegador/dispositivo del usuario
        else {
            $browserLanguage = $request->getPreferredLanguage(['es', 'en']);
            if ($browserLanguage) {
                session(['locale' => $browserLanguage]);
                App::setLocale($browserLanguage);
            }
            // 5. Si nada funciona, usar el default
            else {
                App::setLocale(config('app.locale'));
            }
        }

        return $next($request);
    }
}
