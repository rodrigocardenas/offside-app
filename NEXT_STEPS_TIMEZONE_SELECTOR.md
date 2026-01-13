# 🌍 PRÓXIMOS PASOS: Selector de Zona Horaria en Perfil

## 📌 Objetivo

Agregar un selector para que los usuarios puedan cambiar su zona horaria desde el perfil.

## 🔧 Cómo Implementar

### Paso 1: Actualizar Ruta (routes/web.php)

Verificar que exista la ruta para actualizar el perfil:
```php
Route::middleware('auth')->group(function () {
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});
```

### Paso 2: Actualizar Controlador (ProfileController)

En `app/Http/Controllers/ProfileController.php`, actualizar el método `update()`:

```php
public function update(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255|unique:users,email,' . Auth::id(),
        'timezone' => 'required|in:' . implode(',', array_keys(\App\Helpers\DateTimeHelper::getAvailableTimezones())),
        // ... otros campos
    ]);

    Auth::user()->update($request->only([
        'name',
        'email',
        'timezone',
        // ... otros campos
    ]));

    return redirect(route('profile.edit'))->with('status', __('Profile updated'));
}
```

### Paso 3: Actualizar Vista (resources/views/profile/edit.blade.php)

Agregar el selector de zona horaria. Buscar la sección de formulario y agregar:

```blade
<!-- AGREGAR ESTO EN EL FORMULARIO -->

<div class="mt-6">
    <x-input-label for="timezone" value="{{ __('Zona Horaria') }}" />
    <select id="timezone" name="timezone" class="block mt-1 w-full border border-gray-300 rounded-md shadow-sm">
        @foreach(\App\Helpers\DateTimeHelper::getAvailableTimezones() as $tz => $label)
            <option value="{{ $tz }}" @selected(auth()->user()->timezone === $tz)>
                {{ $label }}
            </option>
        @endforeach
    </select>
    <p class="text-xs text-gray-500 mt-2">
        {{ __('Los horarios de los partidos se mostrarán en esta zona horaria') }}
    </p>
</div>

<div class="mt-6">
    <x-primary-button>{{ __('Guardar Cambios') }}</x-primary-button>
</div>
```

## 🧪 Verificar que Funciona

### Test 1: Cambiar Zona Horaria en Tinker

```bash
php artisan tinker

>>> $user = App\Models\User::find(1)
>>> $user->timezone = 'America/Bogota'
>>> $user->save()

>>> echo "Zona actual: " . $user->timezone
# Output: Zona actual: America/Bogota
```

### Test 2: Verificar Conversión

```bash
php artisan tinker

>>> $user = App\Models\User::find(1)
>>> $match = App\Models\FootballMatch::first()

# Ver hora UTC
>>> echo $match->date->format('Y-m-d H:i')
# Output: 2026-01-14 19:30

# Ver hora convertida al usuario
>>> echo \App\Helpers\DateTimeHelper::toUserTimezone($match->date)
# Output: 2026-01-14 14:30 (UTC-5 para Bogotá)

# Cambiar zona
>>> $user->timezone = 'Europe/Madrid'
>>> $user->save()

>>> echo \App\Helpers\DateTimeHelper::toUserTimezone($match->date)
# Output: 2026-01-14 20:30 (UTC+1 para Madrid)
```

### Test 3: Verificar en Vistas

Ir a cualquier página que muestre horarios (preguntas, chat, etc.) y verificar que cambia según la zona seleccionada.

## 🎨 Opción: Detección Automática (JavaScript)

Si prefieres detectar automáticamente la zona horaria del navegador:

```blade
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Detectar zona horaria del navegador
        const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        
        // Obtener select de timezone
        const tzSelect = document.getElementById('timezone');
        
        if (tzSelect) {
            // Si la zona del navegador existe en el select, seleccionarla
            if (Array.from(tzSelect.options).some(opt => opt.value === timezone)) {
                tzSelect.value = timezone;
            }
        }
        
        // Opcionalmente, auto-enviar el formulario
        // form.submit();
    });
</script>
@endpush
```

## 📝 Ejemplo Completo del Selector

```blade
<!-- En resources/views/profile/edit.blade.php -->

<div class="px-4 py-6 bg-white border-b border-gray-200">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <h3 class="text-lg font-medium text-gray-900">{{ __('Preferencias de Zona Horaria') }}</h3>
        
        <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
            @csrf
            @method('patch')

            <div class="max-w-xl">
                <x-input-label for="timezone" value="{{ __('Zona Horaria') }}" />
                
                <select 
                    id="timezone" 
                    name="timezone" 
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    required
                >
                    @foreach(\App\Helpers\DateTimeHelper::getAvailableTimezones() as $tz => $label)
                        <option 
                            value="{{ $tz }}" 
                            @selected(auth()->user()->timezone === $tz)
                        >
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

                <p class="mt-2 text-sm text-gray-500">
                    {{ __('Los horarios de los partidos se mostrarán en esta zona horaria') }}
                </p>

                @if ($errors->has('timezone'))
                    <p class="mt-2 text-sm text-red-600">{{ $errors->first('timezone') }}</p>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <x-primary-button>{{ __('Guardar') }}</x-primary-button>

                @if (session('status') === 'profile-updated')
                    <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-gray-600">{{ __('Guardado correctamente') }}</p>
                @endif
            </div>
        </form>
    </div>
</div>
```

## ✅ Validación Final

Después de implementar:

1. ✅ El formulario muestra todas las zonas horarias
2. ✅ La zona actual está seleccionada
3. ✅ Cambiar y guardar funciona
4. ✅ Los horarios en otras vistas cambian automáticamente
5. ✅ Funciona en diferentes navegadores

## 🚀 Resultado Esperado

```
ANTES (sin selector):
- Todo usuario ve horarios en UTC (confuso para Latinoamérica)

DESPUÉS (con selector):
- Usuario selecciona su zona horaria una sola vez
- Todos los horarios se muestran automáticamente en su zona
- Cada usuario ve su hora local correctamente
```

## 📞 Soporte

Si necesitas ayuda:
- Revisa [COMPLETE_TIMEZONE_SOLUTION.md](COMPLETE_TIMEZONE_SOLUTION.md)
- Revisa [TIMEZONE_SOLUTION.md](TIMEZONE_SOLUTION.md)
- Ejecuta: `php artisan tinker` para testing

---

**Tiempo estimado de implementación: 10-15 minutos** ⏱️
