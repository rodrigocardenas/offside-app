<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
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
        
        $competitions = Competition::orderBy('name')->get();

        // Load clubs for the selected competition
        $clubs = collect();
        if ($user && $user->favorite_competition_id) {
            $clubs = Team::where('type', 'club')
                ->whereHas('competitions', function ($query) use ($user) {
                    $query->where('competitions.id', $user->favorite_competition_id);
                })
                ->orderBy('name')
                ->get();
        }

        $nationalTeams = Team::where('type', 'national')
            ->orderBy('name')
            ->get();

        return view('profile.edit', compact('user', 'competitions', 'clubs', 'nationalTeams'));
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        Log::info('ProfileController::update iniciado');

        $user = auth()->user();
        $data = $request->validated();

        // Procesar avatar si se subió
        if ($request->hasFile('avatar')) {
            Log::info('Avatar detectado en request, procesando...');
            try {
                $file = $request->file('avatar');
                
                // Generar nombre único
                $filename = 'avatar_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
                
                // Guardar en storage/app/public/avatars
                $path = $file->storeAs('avatars', $filename, 'public');
                Log::info('Avatar guardado en: ' . $path);
                
                // Guardar solo el nombre del archivo en la BD
                $data['avatar'] = $filename;
                
            } catch (\Exception $e) {
                Log::error('Error guardando avatar: ' . $e->getMessage(), [
                    'exception' => $e,
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
                // Continuar sin avatar si hay error
                unset($data['avatar']);
            }
        } else {
            // Si no hay archivo, no actualizar el campo avatar
            unset($data['avatar']);
        }

        $user->update($data);
        Log::info('Perfil actualizado exitosamente');

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function getClubsByCompetition($competitionId)
    {
        $teams = Team::where('type', 'club')
            ->whereHas('competitions', function ($query) use ($competitionId) {
                $query->where('competitions.id', $competitionId);
            })
            ->orderBy('name')
            ->get();

        return response()->json($teams);
    }

    public function getNationalTeams()
    {
        $teams = Team::where('type', 'national')
            ->orderBy('name')
            ->get();

        return response()->json($teams);
    }
}
