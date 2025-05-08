<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TemplateQuestion;
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
        return view('admin.template-questions.create');
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
        ]);

        $templateQuestion = TemplateQuestion::create([
            'type' => $validated['type'],
            'text' => $validated['text'],
            'is_featured' => $validated['is_featured'] ?? false,
            'options' => $validated['options'] ?? []
        ]);

        return redirect()->route('admin.template-questions.index')
            ->with('success', 'Plantilla de pregunta creada correctamente');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TemplateQuestion $templateQuestion)
    {
        return view('admin.template-questions.edit', compact('templateQuestion'));
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
        ]);

        // retornar con mensaje de error si no se valida
        if ($validated['type'] === 'predictive' && count($validated['options']) < 2) {
            return redirect()->back()->with('error', 'Debes agregar al menos 2 opciones para la pregunta predictiva');
        }

        $templateQuestion->update([
            'type' => $validated['type'],
            'text' => $validated['text'],
            'is_featured' => $validated['is_featured'] ?? false,
            'options' => $validated['options'] ?? []
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
