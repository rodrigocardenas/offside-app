<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class QuestionAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin']);
    }

    public function index()
    {
        $questions = Question::with('options')
            ->latest()
            ->paginate(15);

        return view('admin.questions.index', compact('questions'));
    }


    public function create()
    {
        return view('admin.questions.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => ['required', Rule::in(['multiple_choice', 'boolean', 'text'])],
            'category' => ['required', Rule::in(['social', 'predictive'])],
            'points' => 'required|integer|min:1',
            'available_until' => 'required|date|after:now',
            'is_featured' => 'boolean',
            'options' => 'required_if:type,multiple_choice|array|min:2',
            'options.*.text' => 'required_if:type,multiple_choice|string|max:255',
            'options.*.is_correct' => 'required_if:type,multiple_choice|boolean',
        ]);

        $question = Question::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'category' => $validated['category'],
            'points' => $validated['points'],
            'available_until' => $validated['available_until'],
            'is_featured' => $validated['is_featured'] ?? false,
            'user_id' => auth()->id(),
        ]);

        if ($validated['type'] === 'multiple_choice') {
            foreach ($validated['options'] as $optionData) {
                $question->options()->create([
                    'text' => $optionData['text'],
                    'is_correct' => $optionData['is_correct'] ?? false,
                ]);
            }
        }

        return redirect()->route('admin.questions.index')
            ->with('success', 'Pregunta creada exitosamente');
    }

    public function edit(Question $question)
    {
        $question->load('options');
        return view('admin.questions.edit', compact('question'));
    }

    public function update(Request $request, Question $question)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => ['required', Rule::in(['multiple_choice', 'boolean', 'text'])],
            'category' => ['required', Rule::in(['social', 'predictive'])],
            'points' => 'required|integer|min:1',
            'available_until' => 'required|date|after:now',
            'is_featured' => 'boolean',
            'options' => 'required_if:type,multiple_choice|array|min:2',
            'options.*.id' => 'nullable|exists:question_options,id',
            'options.*.text' => 'required_if:type,multiple_choice|string|max:255',
            'options.*.is_correct' => 'required_if:type,multiple_choice|boolean',
        ]);

        $question->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'category' => $validated['category'],
            'points' => $validated['points'],
            'available_until' => $validated['available_until'],
            'is_featured' => $validated['is_featured'] ?? false,
        ]);

        if ($validated['type'] === 'multiple_choice') {
            $existingOptionIds = collect($validated['options'])
                ->pluck('id')
                ->filter()
                ->toArray();

            foreach ($validated['options'] as $optionData) {
                if (isset($optionData['id'])) {
                    $option = $question->options()->find($optionData['id']);
                    if ($option) {
                        $option->update([
                            'text' => $optionData['text'],
                            'is_correct' => $optionData['is_correct'] ?? false,
                        ]);
                    }
                } else {
                    $newOption = $question->options()->create([
                        'text' => $optionData['text'],
                        'is_correct' => $optionData['is_correct'] ?? false,
                    ]);
                    $existingOptionIds[] = $newOption->id;
                }
            }

            $question->options()->whereNotIn('id', $existingOptionIds)->delete();
        } else {
            $question->options()->delete();
        }

        return redirect()->route('admin.questions.index')
            ->with('success', 'Pregunta actualizada exitosamente');
    }

    public function destroy(Question $question)
    {
        $question->delete();
        return redirect()->route('admin.questions.index')
            ->with('success', 'Pregunta eliminada exitosamente');
    }

    public function toggleFeatured(Question $question)
    {
        $question->update([
            'is_featured' => !$question->is_featured
        ]);

        return response()->json([
            'success' => true,
            'is_featured' => $question->is_featured
        ]);
    }
}
