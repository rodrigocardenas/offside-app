<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TemplateQuestion;
use App\Models\Competition;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TemplateQuestionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $templateQuestions = TemplateQuestion::latest()->paginate(10);
        return view('admin.template-questions.index', compact('templateQuestions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $competitions = Competition::all(); // Obtener todas las competencias
        return view('admin.template-questions.create', compact('competitions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'type' => 'required|in:predictive,social',
                'text' => 'required|string|max:255',
                'is_featured' => 'sometimes|boolean',
                'competition_id' => 'nullable|exists:competitions,id|required_if:type,predictive',
                'home_team_id' => 'nullable|exists:teams,id|required_if:type,predictive',
                'away_team_id' => 'nullable|exists:teams,id|required_if:type,predictive',
                'football_match_id' => 'nullable|exists:football_matches,id',
                'options' => 'required_if:type,predictive|array|min:2',
                'options.*.text' => 'required_if:type,predictive|string|max:255',
            ]);

            // Log de los datos validados
            \Log::info('Datos validados:', $validated);

            $templateQuestion = TemplateQuestion::create($validated);

            if ($request->has('options')) {
                foreach ($request->options as $option) {
                    $templateQuestion->options()->create([
                        'text' => $option['text'],
                        'is_correct' => $option['is_correct'] ?? false
                    ]);
                }
            }

            return redirect()->route('admin.template-questions.index')
                ->with('success', 'Pregunta creada exitosamente.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Log de los errores de validación
            \Log::error('Errores de validación:', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);

            return back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            // Log de otros errores
            \Log::error('Error al crear pregunta:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return back()
                ->with('error', 'Error al crear la pregunta: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TemplateQuestion $templateQuestion)
    {
        $competitions = Competition::all(); // Obtener todas las competencias
        return view('admin.template-questions.edit', compact('templateQuestion', 'competitions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TemplateQuestion $templateQuestion)
    {
        $validated = $request->validate([
            'type' => 'required',
            'text' => 'required|string|max:255',
            'is_featured' => 'sometimes|boolean',
            'options' => 'required_if:type,predictive|array|min:2',
            'options.*.text' => 'required_if:type,predictive|string|max:255',
            'competition_id' => 'nullable|exists:competitions,id', // Validar competencia
            'used_at' => 'sometimes|boolean', // Validar si se marcó como usada
        ]);

        $templateQuestion->update([
            'type' => $validated['type'],
            'text' => $validated['text'],
            'is_featured' => $validated['is_featured'] ?? false,
            'options' => $validated['options'] ?? [],
            'competition_id' => $validated['competition_id'], // Actualizar competencia
            'used_at' => $request->has('used_at') ? now() : null, // Marcar como usada o no
        ]);

        return redirect()->route('admin.template-questions.index')
            ->with('success', 'Plantilla de pregunta actualizada correctamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TemplateQuestion $templateQuestion)
    {
        $templateQuestion->delete();
        return redirect()->route('admin.template-questions.index')
            ->with('success', 'Plantilla de pregunta eliminada correctamente');
    }
}
