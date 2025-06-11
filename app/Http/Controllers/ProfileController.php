<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Competition;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use App\Http\Requests\ProfileUpdateRequest;

class ProfileController extends Controller
{
    /**
     * Show the form for editing the user's profile.
     */
    public function edit()
    {
        $user = auth()->user();
        $competitions = Competition::all();

        // Si el usuario tiene una competencia favorita, solo mostramos los clubes de esa competencia
        if ($user->favorite_competition_id) {
            $clubs = Team::where('type', 'club')->get();
        } else {
            $clubs = collect(); // Lista vacía si no hay competencia seleccionada
        }

        $nationalTeams = Team::where('type', 'national')->get();

        return view('profile.edit', compact('user', 'competitions', 'clubs', 'nationalTeams'));
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }
}
