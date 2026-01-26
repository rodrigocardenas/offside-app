<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DateTimeHelper
{
    /**
     * Convertir una fecha UTC a la zona horaria del usuario
     *
     * @param Carbon|string $date Fecha en UTC
     * @param string $format Formato deseado
     * @param string|null $timezone Zona horaria (si no se proporciona, usa la del usuario)
     * @return string
     */
    public static function toUserTimezone($date, $format = 'd/m/Y H:i', $timezone = null)
    {
        // Obtener zona horaria del usuario o usar la por defecto
        if (!$timezone && Auth::check()) {
            $timezone = Auth::user()->timezone ?? config('app.timezone');
        } elseif (!$timezone) {
            $timezone = config('app.timezone');
        }

        // Si es string, asumir que está en UTC (formato de BD)
        if (is_string($date)) {
            $date = Carbon::createFromFormat('Y-m-d H:i:s', $date, 'UTC');
        } else {
            // Si es Carbon object desde el modelo, Laravel ya aplicó app.timezone
            // así que viene en ese timezone. Necesitamos extraer la hora sin cambios
            // La clave es: la hora mostrada es la real,pero el timezone está mal marcado
            $date = $date->copy();

            // El objeto está en app.timezone, necesitamos interpretarlo como UTC
            // para poder convertirlo correctamente a la zona deseada
            $appTimezone = config('app.timezone');

            // Obtener la hora actual (que es la UTC real)
            $hour = $date->format('Y-m-d H:i:s');

            // Re-crear como UTC
            $date = Carbon::createFromFormat('Y-m-d H:i:s', $hour, 'UTC');
        }

        return $date->setTimezone($timezone)->format($format);
    }

    /**
     * Obtener la hora UTC formateada
     *
     * @param Carbon|string $date
     * @param string $format
     * @return string
     */
    public static function toUTC($date, $format = 'd/m/Y H:i')
    {
        if (is_string($date)) {
            $date = Carbon::createFromFormat('Y-m-d H:i:s', $date, 'UTC');
        } else {
            // Si es Carbon object, ya está en app.timezone
            // Necesitamos extraer la hora sin cambios (porque eso es realmente UTC)
            $date = $date->copy();
            $hour = $date->format('Y-m-d H:i:s');

            // Re-crear como UTC
            $date = Carbon::createFromFormat('Y-m-d H:i:s', $hour, 'UTC');
        }

        return $date->format($format);
    }

    /**
     * Convertir fecha de zona horaria local a UTC (para guardar)
     *
     * @param string $date
     * @param string $timezone
     * @return Carbon
     */
    public static function toUTCFromLocal($date, $timezone = null)
    {
        if (!$timezone && Auth::check()) {
            $timezone = Auth::user()->timezone ?? config('app.timezone');
        } elseif (!$timezone) {
            $timezone = config('app.timezone');
        }

        return Carbon::createFromFormat('Y-m-d H:i:s', $date, $timezone)->setTimezone('UTC');
    }

    /**
     * Obtener todas las zonas horarias disponibles
     *
     * @return array
     */
    public static function getAvailableTimezones()
    {
        return [
            'UTC' => 'UTC (Coordinada)',
            'America/Argentina/Buenos_Aires' => 'Argentina (UTC-3)',
            'America/Bogota' => 'Colombia (UTC-5)',
            'America/Lima' => 'Perú (UTC-5)',
            'America/Mexico_City' => 'México (UTC-6)',
            'America/New_York' => 'Nueva York (UTC-5)',
            'America/Los_Angeles' => 'Los Ángeles (UTC-8)',
            'Europe/London' => 'Londres (UTC+0)',
            'Europe/Madrid' => 'Madrid (UTC+1)',
            'Europe/Paris' => 'París (UTC+1)',
            'Europe/Berlin' => 'Berlín (UTC+1)',
            'Europe/Rome' => 'Roma (UTC+1)',
            'Asia/Tokyo' => 'Tokio (UTC+9)',
            'Asia/Shanghai' => 'Shanghái (UTC+8)',
            'Australia/Sydney' => 'Sídney (UTC+11)',
        ];
    }

    /**
     * Obtener timestamp ISO 8601 en la zona horaria del usuario
     * Usado para JavaScript countdowns que respetan la zona horaria local
     *
     * @param Carbon|string $date Fecha en UTC
     * @param string|null $timezone Zona horaria (si no se proporciona, usa la del usuario)
     * @return string Timestamp ISO 8601 en formato 'Y-m-d H:i:s'
     */
    public static function toUserTimestampForCountdown($date, $timezone = null)
    {
        // Obtener zona horaria del usuario o usar la por defecto
        if (!$timezone && Auth::check()) {
            $timezone = Auth::user()->timezone ?? config('app.timezone');
        } elseif (!$timezone) {
            $timezone = config('app.timezone');
        }

        // Si es string, asumir que está en UTC (formato de BD)
        if (is_string($date)) {
            $date = Carbon::createFromFormat('Y-m-d H:i:s', $date, 'UTC');
        } else {
            // Si es Carbon object desde el modelo, Laravel ya aplicó app.timezone
            $date = $date->copy();
            $hour = $date->format('Y-m-d H:i:s');
            // Re-crear como UTC
            $date = Carbon::createFromFormat('Y-m-d H:i:s', $hour, 'UTC');
        }

        // Convertir a zona horaria del usuario y retornar en formato legible
        return $date->setTimezone($timezone)->format('Y-m-d H:i:s');
    }
}
