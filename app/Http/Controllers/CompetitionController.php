<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use Illuminate\Http\Request;

class CompetitionController extends Controller
{
    public function index()
    {
        $competitions = Competition::all();
        return view('competitions.index', compact('competitions'));
    }

    public function create()
    {
        return view('competitions.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:champions,laliga,premier',
            'country' => 'nullable|string|max:255',
        ]);

        Competition::create($request->validated());

        return redirect()->route('competitions.index')
            ->with('success', 'Competición creada exitosamente.');
    }

    public function edit(Competition $competition)
    {
        return view('competitions.edit', compact('competition'));
    }

    public function update(Request $request, Competition $competition)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:champions,laliga,premier',
            'country' => 'nullable|string|max:255',
        ]);

        $competition->update($request->validated());

        return redirect()->route('competitions.index')
            ->with('success', 'Competición actualizada exitosamente.');
    }

    public function destroy(Competition $competition)
    {
        if ($competition->groups()->exists()) {
            return back()->with('error', 'No se puede eliminar una competición que tiene grupos asociados.');
        }

        $competition->delete();

        return redirect()->route('competitions.index')
            ->with('success', 'Competición eliminada exitosamente.');
    }

    // get teams for a competition
    public function getTeams(Competition $competition)
    {
        try {
            $teams = $competition->teams()->get();
            return response()->json($teams);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
