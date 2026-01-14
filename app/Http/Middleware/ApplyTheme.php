<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyTheme
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $theme = auth()->user()->theme ?? 'dark';
            $themeMode = auth()->user()->theme_mode ?? 'light';
            view()->share('userTheme', $theme);
            view()->share('userThemeMode', $themeMode);
        } else {
            view()->share('userTheme', 'dark');
            view()->share('userThemeMode', 'light');
        }

        // Detectar si está en modo dark
        $isDark = ($themeMode ?? 'light') === 'dark';

        // Colores para tema light
        if (!$isDark) {
            $bgPrimary = '#ffffff';
            $bgSecondary = '#f8f9fa';
            $bgTertiary = '#f0f0f0';
            $textPrimary = '#333333';
            $textSecondary = '#666666';
            $borderColor = '#e0e0e0';
            $componentsBackground = '#ffffff';
            $buttonBgHover = '#e9ecef';
        } else {
            // Colores para tema dark
            $bgPrimary = '#1a1a1a';
            $bgSecondary = '#2a2a2a';
            $bgTertiary = '#333333';
            $textPrimary = '#ffffff';
            $textSecondary = '#b0b0b0';
            $borderColor = '#333333';
            $componentsBackground = '#1a524e';
            $buttonBgHover = '#2a2a2a';
        }

        $accentColor = '#00deb0';
        $accentDark = '#003b2f';

        // Compartir todas las variables de tema globalmente
        view()->share([
            'themeColors' => [
                'bgPrimary' => $bgPrimary,
                'bgSecondary' => $bgSecondary,
                'bgTertiary' => $bgTertiary,
                'textPrimary' => $textPrimary,
                'textSecondary' => $textSecondary,
                'borderColor' => $borderColor,
                'componentsBackground' => $componentsBackground,
                'buttonBgHover' => $buttonBgHover,
                'accentColor' => $accentColor,
                'accentDark' => $accentDark,
            ],
            'isDark' => $isDark,
            'bgPrimary' => $bgPrimary,
            'bgSecondary' => $bgSecondary,
            'bgTertiary' => $bgTertiary,
            'textPrimary' => $textPrimary,
            'textSecondary' => $textSecondary,
            'borderColor' => $borderColor,
            'componentsBackground' => $componentsBackground,
            'buttonBgHover' => $buttonBgHover,
            'accentColor' => $accentColor,
            'accentDark' => $accentDark,
        ]);

        return $next($request);
    }
}
