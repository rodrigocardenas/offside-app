<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('admin');
    }

    public function rules(): array
    {
        $currentYear = (int) now()->year;

        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:club,national'],
            'short_name' => ['nullable', 'string', 'max:255'],
            'tla' => ['nullable', 'string', 'max:10'],
            'country' => ['nullable', 'string', 'max:120'],
            'crest_url' => ['nullable', 'url', 'max:2048'],
            'website' => ['nullable', 'url', 'max:2048'],
            'founded_year' => ['nullable', 'integer', 'min:1800', 'max:' . ($currentYear + 1)],
            'club_colors' => ['nullable', 'string', 'max:255'],
            'venue' => ['nullable', 'string', 'max:255'],
            'stadium_id' => ['nullable', 'exists:stadiums,id'],
            'external_id' => ['nullable', 'string', 'max:191', 'unique:teams,external_id'],
        ];
    }
}
