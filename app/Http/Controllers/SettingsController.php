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
            'theme_mode' => 'nullable|in:light,dark',
            'language' => 'nullable|in:es,en',
        ]);

        // Guardar preferencia de tema
        if ($request->has('theme_mode')) {
            $user->update(['theme_mode' => $validated['theme_mode'] ?? 'light']);
        }

        // Guardar preferencia de idioma
        if ($request->has('language')) {
            $user->update(['language' => $validated['language']]);
            session(['locale' => $validated['language']]);
        }

        return redirect()
            ->route('settings.index')
            ->with('success', __('messages.success'));
    }

    /**
     * Update notification preferences (AJAX).
     */
    public function updateNotifications(Request $request)
    {
        $validTypes = [
            'chat_message',
            'new_predictive_questions',
            'social_question',
            'predictive_results',
            'ranking_overtaken',
            'daily_unanswer_reminder',
            'featured_question',
            'daily_points_earned',
        ];

        $request->validate([
            'type'    => 'required|in:' . implode(',', $validTypes),
            'enabled' => 'required|boolean',
        ]);

        $user = Auth::user();
        $preferences = $user->notification_preferences ?? [];
        $preferences[$request->input('type')] = (bool) $request->input('enabled');
        $user->update(['notification_preferences' => $preferences]);

        return response()->json(['ok' => true]);
    }
}
