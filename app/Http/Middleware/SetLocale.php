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
        // Si el usuario está autenticado, usar su idioma preferido
        if (auth()->check() && auth()->user()->language) {
            App::setLocale(auth()->user()->language);
        }
        // Si hay idioma en la sesión, usarlo
        elseif (session()->has('locale')) {
            App::setLocale(session('locale'));
        }
        // Si hay idioma en los parámetros de query, guardarlo
        elseif ($request->has('locale')) {
            $locale = $request->query('locale');
            if (in_array($locale, ['es', 'en'])) {
                session(['locale' => $locale]);
                App::setLocale($locale);
            }
        }

        return $next($request);
    }
}
