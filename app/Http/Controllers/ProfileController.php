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
use App\Facades\CloudflareImages;

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
                
                // Intentar subir a Cloudflare si está habilitado
                if (config('cloudflare.images.enabled')) {
                    try {
                        // Eliminar avatar anterior de Cloudflare si existe
                        if ($user->avatar_provider === 'cloudflare' && $user->avatar_cloudflare_id) {
                            try {
                                CloudflareImages::delete($user->avatar_cloudflare_id);
                                Log::info('Avatar anterior de Cloudflare eliminado: ' . $user->avatar_cloudflare_id);
                            } catch (\Exception $e) {
                                Log::warning('Error eliminando avatar anterior de Cloudflare: ' . $e->getMessage());
                            }
                        }

                        // Subir nuevo avatar a Cloudflare
                        $uploadResponse = CloudflareImages::upload(
                            fopen($file->getRealPath(), 'r'),
                            'avatar_' . $user->id . '_' . time(),
                            ['user_id' => $user->id]
                        );

                        if ($uploadResponse && isset($uploadResponse['result']['id'])) {
                            $data['avatar_cloudflare_id'] = $uploadResponse['result']['id'];
                            $data['avatar_provider'] = 'cloudflare';
                            // No actualizar el campo 'avatar' cuando usamos Cloudflare
                            unset($data['avatar']);
                            
                            Log::info('Avatar subido a Cloudflare exitosamente', [
                                'cloudflare_id' => $uploadResponse['result']['id']
                            ]);
                        } else {
                            throw new Exception('Invalid Cloudflare response');
                        }
                    } catch (\Exception $e) {
                        Log::warning('Error subiendo a Cloudflare, usando almacenamiento local: ' . $e->getMessage());
                        // Fallback a storage local
                        $this->storeAvatarLocally($file, $user, $data);
                    }
                } else {
                    // Cloudflare deshabilitado, usar storage local
                    $this->storeAvatarLocally($file, $user, $data);
                }
                
            } catch (\Exception $e) {
                Log::error('Error procesando avatar: ' . $e->getMessage(), [
                    'exception' => $e,
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
                // Continuar sin avatar si hay error
                unset($data['avatar']);
                unset($data['avatar_cloudflare_id']);
                unset($data['avatar_provider']);
            }
        } else {
            // Si no hay archivo, no actualizar los campos avatar
            unset($data['avatar']);
            unset($data['avatar_cloudflare_id']);
            unset($data['avatar_provider']);
        }

        $user->update($data);
        Log::info('Perfil actualizado exitosamente');

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Store avatar in local storage.
     * Helper method for fallback storage.
     */
    private function storeAvatarLocally($file, $user, &$data): void
    {
        $filename = 'avatar_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
        
        // Guardar en storage/app/public/avatars
        $path = $file->storeAs('avatars', $filename, 'public');
        Log::info('Avatar guardado localmente en: ' . $path);
        
        // Guardar solo el nombre del archivo en la BD
        $data['avatar'] = $filename;
        $data['avatar_provider'] = 'local';
        unset($data['avatar_cloudflare_id']);
        
        // Eliminar avatar anterior de Cloudflare si existe
        if ($user->avatar_provider === 'cloudflare' && $user->avatar_cloudflare_id) {
            try {
                CloudflareImages::delete($user->avatar_cloudflare_id);
                Log::info('Avatar anterior de Cloudflare eliminado al cambiar a local: ' . $user->avatar_cloudflare_id);
            } catch (\Exception $e) {
                Log::warning('Error eliminando avatar anterior de Cloudflare: ' . $e->getMessage());
            }
        }
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

