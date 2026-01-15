<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTeamRequest;
use App\Http\Requests\Admin\UpdateTeamRequest;
use App\Models\Stadium;
use App\Models\Team;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin']);
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $type = $request->input('type');
        $country = $request->input('country');

        $query = Team::query()->with('stadium');

        if ($search !== '') {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('short_name', 'like', "%{$search}%")
                    ->orWhere('country', 'like', "%{$search}%");
            });
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($country) {
            $query->where('country', $country);
        }

        /** @var LengthAwarePaginator $teams */
        $teams = $query
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $typeStats = Team::selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $countries = Team::query()
            ->select('country')
            ->whereNotNull('country')
            ->distinct()
            ->orderBy('country')
            ->pluck('country')
            ->all();

        return view('admin.teams.index', [
            'teams' => $teams,
            'filters' => [
                'search' => $search,
                'type' => $type,
                'country' => $country,
            ],
            'stats' => [
                'total' => Team::count(),
                'clubs' => $typeStats['club'] ?? 0,
                'national' => $typeStats['national'] ?? 0,
            ],
            'countries' => $countries,
        ]);
    }

    public function create(): View
    {
        return view('admin.teams.create', [
            'team' => new Team(),
            'stadiums' => $this->getStadiumOptions(),
        ]);
    }

    public function store(StoreTeamRequest $request): RedirectResponse
    {
        $team = Team::create($request->validated());

        return redirect()
            ->route('admin.teams.edit', $team)
            ->with('success', 'Equipo creado correctamente.');
    }

    public function edit(Team $team): View
    {
        return view('admin.teams.edit', [
            'team' => $team,
            'stadiums' => $this->getStadiumOptions(),
        ]);
    }

    public function update(UpdateTeamRequest $request, Team $team): RedirectResponse
    {
        $team->update($request->validated());

        return redirect()
            ->route('admin.teams.edit', $team)
            ->with('success', 'Equipo actualizado correctamente.');
    }

    public function destroy(Team $team): RedirectResponse
    {
        $team->delete();

        return redirect()
            ->route('admin.teams.index')
            ->with('success', 'Equipo eliminado.');
    }

    private function getStadiumOptions(): array
    {
        return [];
    }
}
