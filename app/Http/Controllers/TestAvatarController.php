<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TestAvatarController extends Controller
{
    public function testUpload(Request $request)
    {
        Log::info('TestAvatarController::testUpload iniciado');

        if ($request->hasFile('avatar')) {
            Log::info('Archivo detectado');

            $file = $request->file('avatar');
            Log::info('Información del archivo:', [
                'name' => $file->getClientOriginalName(),
                'extension' => $file->getClientOriginalExtension(),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
                'isValid' => $file->isValid()
            ]);

            if ($file->isValid()) {
                try {
                    // Método 1: Usar storeAs
                    $filename1 = 'test1_' . time() . '.' . ($file->getClientOriginalExtension() ?: 'jpg');
                    Log::info('Intentando método 1 con: ' . $filename1);
                    $file->storeAs('avatars', $filename1, 'public');
                    Log::info('Método 1 exitoso');

                    // Método 2: Usar move
                    $filename2 = 'test2_' . time() . '.' . ($file->getClientOriginalExtension() ?: 'jpg');
                    Log::info('Intentando método 2 con: ' . $filename2);
                    $path = storage_path('app/public/avatars');
                    if (!is_dir($path)) {
                        mkdir($path, 0755, true);
                    }
                    $file->move($path, $filename2);
                    Log::info('Método 2 exitoso');

                    return response()->json([
                        'success' => true,
                        'message' => 'Archivo subido correctamente',
                        'files' => [$filename1, $filename2]
                    ]);

                } catch (\Exception $e) {
                    Log::error('Error en testUpload: ' . $e->getMessage(), [
                        'file' => $e->getFile(),
                        'line' => $e->getLine()
                    ]);

                    return response()->json([
                        'success' => false,
                        'error' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine()
                    ], 500);
                }
            } else {
                Log::warning('Archivo no válido');
                return response()->json(['success' => false, 'error' => 'Archivo no válido'], 400);
            }
        } else {
            Log::info('No se detectó archivo');
            return response()->json(['success' => false, 'error' => 'No se detectó archivo'], 400);
        }
    }
}
