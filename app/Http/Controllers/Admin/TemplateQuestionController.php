<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TemplateQuestion;
use App\Models\Competition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Jobs\UpdateAnswersPoints;
use Illuminate\Validation\Rule;

class TemplateQuestionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $templateQuestions = TemplateQuestion::with('competition')->paginate(10);
        return view('admin.template-questions.index', compact('templateQuestions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $competitions = Competition::all();
        return view('admin.template-questions.create', compact('competitions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => ['required', Rule::in(['predictive', 'social'])],
            'options' => 'required_if:type,predictive|array|min:2',
            'options.*.text' => 'required_if:type,predictive|string|max:255',
            'options.*.is_correct' => 'sometimes',
            'competition_id' => 'required|exists:competitions,id',
        ]);

        $templateQuestion = TemplateQuestion::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'options' => $validated['options'] ?? [],
            'competition_id' => $validated['competition_id'],
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('admin.template-questions.index')
            ->with('success', __('controllers.template_questions.created_successfully'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TemplateQuestion $templateQuestion)
    {
        $competitions = Competition::all();
        return view('admin.template-questions.edit', compact('templateQuestion', 'competitions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TemplateQuestion $templateQuestion)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'type' => ['required', Rule::in(['predictive', 'social'])],
                'options' => 'required_if:type,predictive|array|min:2',
                'options.*.text' => 'required_if:type,predictive|string|max:255',
                'options.*.is_correct' => 'sometimes',
                'competition_id' => 'required|exists:competitions,id',
            ]);

            $oldOptions = $templateQuestion->options;

            $templateQuestion->update([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'type' => $validated['type'],
                'competition_id' => $validated['competition_id'],
            ]);

            if (isset($validated['options'])) {
                foreach ($validated['options'] as $option) {
                    $templateQuestion->options = array_merge($templateQuestion->options ?? [], [$option]);
                }
                $templateQuestion->save();
            }

            return redirect()->route('admin.template-questions.index')
                ->with('success', __('controllers.template_questions.updated_successfully'));
        } catch (\Exception $e) {
            Log::error('Error al actualizar la plantilla de pregunta: ' . $e->getMessage());
            return back()->with('error', __('controllers.template_questions.update_error'));
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TemplateQuestion $templateQuestion)
    {
        try {
            $templateQuestion->delete();
            return redirect()->route('admin.template-questions.index')
                ->with('success', __('controllers.template_questions.deleted_successfully'));
        } catch (\Exception $e) {
            Log::error('Error al eliminar la plantilla de pregunta: ' . $e->getMessage());
            return back()->with('error', __('controllers.template_questions.delete_error'));
        }
    }
}
