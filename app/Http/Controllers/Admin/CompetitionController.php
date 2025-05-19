<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\FootballMatch;
use Illuminate\Http\JsonResponse;

class CompetitionController extends Controller
{
    /**
     * Get teams for a competition
     */
    public function getTeams(Competition $competition): JsonResponse
    {
        try {
            $teams = $competition->teams()->select('id', 'name')->get();
            return response()->json($teams);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get matches for a competition
     */
    public function getMatches(Competition $competition): JsonResponse
    {
        try {
            $matches = FootballMatch::where('competition_id', $competition->id)
                ->with(['homeTeam:id,name', 'awayTeam:id,name'])
                ->orderBy('date')
                ->get()
                ->map(function ($match) {
                    return [
                        'id' => $match->id,
                        'home_team_id' => $match->home_team_id,
                        'away_team_id' => $match->away_team_id,
                        'home_team_name' => $match->homeTeam->name ?? '',
                        'away_team_name' => $match->awayTeam->name ?? '',
                        'match_date' => $match->date,
                    ];
                });
            return response()->json($matches);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get a specific match
     */
    public function getMatch(FootballMatch $match): JsonResponse
    {
        try {
            return response()->json([
                'id' => $match->id,
                'home_team_id' => $match->home_team_id,
                'away_team_id' => $match->away_team_id,
                'home_team_name' => $match->homeTeam->name ?? '',
                'away_team_name' => $match->awayTeam->name ?? '',
                'date' => $match->date,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
