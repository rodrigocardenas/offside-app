<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TemplateQuestion;
use App\Models\Competition;
use App\Models\Question;
use Illuminate\Http\Request;

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
        $validated = $request->validate([
            'type' => 'required',
            'text' => 'required|string|max:255',
            'is_featured' => 'sometimes|boolean',
            'options' => 'required_if:type,predictive|array|min:2',
            'options.*.text' => 'required_if:type,predictive|string|max:255',
            'competition_id' => 'nullable|exists:competitions,id', // Validar competencia
        ]);

        $templateQuestion = TemplateQuestion::create([
            'type' => $validated['type'],
            'text' => $validated['text'],
            'is_featured' => $validated['is_featured'] ?? false,
            'options' => $validated['options'] ?? [],
            'competition_id' => $validated['competition_id'], // Guardar competencia
        ]);

        // si se agregó la opción con el checkbox "is_correct", se setean los puntos a todas las answers que tengan la pregunta
        // $questions = Question::where('template_question_id', $templateQuestion->id)->each(function ($question) use ($templateQuestion) {
        //     $question->answers->update([
        //         'points' => $validated['is_featured'] ? 400 : 300,
        //     ]);
        // });

        return redirect()->route('admin.template-questions.index')
            ->with('success', 'Plantilla de pregunta creada correctamente');
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
