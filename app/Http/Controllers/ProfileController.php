<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class ProfileController extends Controller
{
    /**
     * Show the form for editing the user's profile.
     */
    public function edit()
    {
        $user = Auth::user();
        return view('profile.edit', compact('user'));
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        // Procesar la imagen de avatar si se subió
        if ($request->hasFile('avatar')) {
            // Crear el directorio si no existe
            if (!Storage::exists('public/avatars')) {
                Storage::makeDirectory('public/avatars');
            }


            // Eliminar el avatar anterior si existe
            if ($user->avatar) {
                Storage::delete('public/avatars/' . $user->avatar);
            }


            $image = $request->file('avatar');
            $filename = Str::uuid() . '.' . $image->getClientOriginalExtension();
            $destinationPath = storage_path('app/public/avatars/');
            
            // Crear directorio si no existe
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            
            // Mover el archivo a la ubicación temporal
            $tempPath = $image->getRealPath();
            if (empty($tempPath)) {
                $tempPath = tempnam(sys_get_temp_dir(), 'img_');
                file_put_contents($tempPath, file_get_contents($image->getPathname()));
            }
            
            // Obtener las dimensiones de la imagen original
            list($width, $height) = getimagesize($tempPath);
            
            // Calcular nuevas dimensiones manteniendo la relación de aspecto
            $newWidth = 200;
            $newHeight = (int) (200 * $height / $width);
            
            // Crear una nueva imagen con las dimensiones deseadas
            $thumb = imagecreatetruecolor($newWidth, $newHeight);
            
            // Cargar la imagen según su tipo
            $source = null;
            $ext = strtolower($image->getClientOriginalExtension());
            
            if ($ext == 'jpg' || $ext == 'jpeg') {
                $source = imagecreatefromjpeg($tempPath);
            } elseif ($ext == 'png') {
                $source = imagecreatefrompng($tempPath);
                // Preservar la transparencia
                imagealphablending($source, false);
                imagesavealpha($source, true);
            } elseif ($ext == 'gif') {
                $source = imagecreatefromgif($tempPath);
            }
            
            if ($source) {
                // Redimensionar la imagen
                imagecopyresized($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                
                // Guardar la imagen
                $fullPath = $destinationPath . $filename;
                if ($ext == 'jpg' || $ext == 'jpeg') {
                    imagejpeg($thumb, $fullPath, 90);
                } elseif ($ext == 'png') {
                    // Configurar la transparencia para PNG
                    imagealphablending($thumb, false);
                    imagesavealpha($thumb, true);
                    imagepng($thumb, $fullPath, 9);
                } elseif ($ext == 'gif') {
                    imagegif($thumb, $fullPath);
                }
                
                // Liberar memoria
                imagedestroy($source);
                imagedestroy($thumb);
            }

            $data['avatar'] = $filename;
        }

        $user->update($data);

        return redirect()->route('profile.edit')
            ->with('success', 'Perfil actualizado exitosamente.');
    }
}
