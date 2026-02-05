<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MatchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'external_id' => $this->external_id,
            'home_team' => [
                'id' => $this->homeTeam?->id,
                'name' => $this->homeTeam?->name ?? $this->home_team,
                'crest_url' => $this->homeTeam?->crest_url,
            ],
            'away_team' => [
                'id' => $this->awayTeam?->id,
                'name' => $this->awayTeam?->name ?? $this->away_team,
                'crest_url' => $this->awayTeam?->crest_url,
            ],
            'kick_off_time' => \Carbon\Carbon::parse($this->match_date)->format('H:i'),
            'kick_off_timestamp' => \Carbon\Carbon::parse($this->match_date)->timestamp,
            'status' => $this->status,
            'score' => [
                'home' => $this->home_team_score,
                'away' => $this->away_team_score,
            ],
            'penalties' => [
                'home' => $this->home_team_penalties,
                'away' => $this->away_team_penalties,
            ],
            'competition' => [
                'id' => $this->competition?->id,
                'name' => $this->competition?->name ?? $this->league,
            ],
            'stage' => $this->matchday,
        ];
    }
}
