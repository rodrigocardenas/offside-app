<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    /**
     * Show the settings page.
     */
    public function index()
    {
        $user = auth()->user();
        return view('settings.index', compact('user'));
    }

    /**
     * Update user settings.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'theme_mode' => 'nullable|in:light,dark,auto',
        ]);

        // Guardar preferencia de tema
        if ($request->has('theme_mode')) {
            $user->update(['theme_mode' => $validated['theme_mode']]);
        }

        return redirect()
            ->route('settings.index')
            ->with('success', 'Configuración actualizada exitosamente.');
    }
}
