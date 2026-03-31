<!-- Pre Match Group Challenges Page -->
@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <span class="text-5xl">🔥</span> {{ $group->name }} - Pre Match Challenges
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">
                Crea desafíos improbables y compite con tu grupo. ¡Adivina qué pasará en el partido!
            </p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-blue-100 dark:bg-blue-900 p-6 rounded-lg">
                <p class="text-sm font-bold text-blue-600 dark:text-blue-300">TOTAL DESAFÍOS</p>
                <p class="text-3xl font-bold text-blue-800 dark:text-blue-200 mt-2">
                    {{ $preMatches->count() }}
                </p>
            </div>
            <div class="bg-green-100 dark:bg-green-900 p-6 rounded-lg">
                <p class="text-sm font-bold text-green-600 dark:text-green-300">ABIERTOS AHORA</p>
                <p class="text-3xl font-bold text-green-800 dark:text-green-200 mt-2">
                    {{ $preMatches->where('status', 'OPEN')->count() }}
                </p>
            </div>
            <div class="bg-purple-100 dark:bg-purple-900 p-6 rounded-lg">
                <p class="text-sm font-bold text-purple-600 dark:text-purple-300">RESUELTOS</p>
                <p class="text-3xl font-bold text-purple-800 dark:text-purple-200 mt-2">
                    {{ $preMatches->where('status', 'RESOLVED')->count() }}
                </p>
            </div>
            <div class="bg-orange-100 dark:bg-orange-900 p-6 rounded-lg">
                <p class="text-sm font-bold text-orange-600 dark:text-orange-300">CASTIGOS PENDIENTES</p>
                <p class="text-3xl font-bold text-orange-800 dark:text-orange-200 mt-2">
                    {{ count($groupPenalties->where('fulfilled_at', null) ?? []) }}
                </p>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="flex gap-2 mb-8 border-b border-gray-300 dark:border-gray-600 overflow-x-auto">
            <button
                onclick="filterMatches('ALL')"
                class="font-bold px-6 py-3 whitespace-nowrap border-b-2"
                id="filterAll"
                style="border-color: rgb(37, 99, 235);"
            >
                📊 Todos
            </button>
            <button
                onclick="filterMatches('OPEN')"
                class="font-bold px-6 py-3 whitespace-nowrap border-b-2 border-transparent text-gray-600 dark:text-gray-400"
                id="filterOpen"
            >
                🔵 Abiertos
            </button>
            <button
                onclick="filterMatches('LOCKED')"
                class="font-bold px-6 py-3 whitespace-nowrap border-b-2 border-transparent text-gray-600 dark:text-gray-400"
                id="filterLocked"
            >
                🔒 Cerrados
            </button>
            <button
                onclick="filterMatches('RESOLVED')"
                class="font-bold px-6 py-3 whitespace-nowrap border-b-2 border-transparent text-gray-600 dark:text-gray-400"
                id="filterResolved"
            >
                ✅ Resueltos
            </button>
        </div>

        <!-- Pre Matches List -->
        <div id="preMatchesContainer" class="space-y-6 mb-12">
            @forelse($preMatches as $preMatch)
                <x-pre-match.card
                    :preMatch="$preMatch"
                    :match="$preMatch->match"
                />
            @empty
                <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                    <p class="text-xl">📭 No hay desafíos creados aún</p>
                    @can('create', App\Models\PreMatch::class)
                        <button
                            onclick="openCreatePreMatchModal()"
                            class="mt-4 bg-blue-600 text-white px-6 py-2 rounded font-bold hover:bg-blue-700 transition"
                        >
                            ➕ Crear Primer Desafío
                        </button>
                    @endcan
                </div>
            @endforelse
        </div>

        <!-- Penalty History -->
        <div class="mt-12 pt-8 border-t border-gray-300 dark:border-gray-600">
            <x-pre-match.penalty-history />
        </div>
    </div>
</div>

<!-- Create Proposal Modal -->
<x-pre-match.create-proposal-modal />

<!-- Resolution Modal (Admin Only) -->
@if(Auth::user()?->isadmin())
    <x-pre-match.resolution-modal />
@endif

<script>
    let allPreMatches = @json($preMatches);
    let currentFilter = 'ALL';

    function filterMatches(status) {
        currentFilter = status;

        // Update button styles
        document.querySelectorAll('[id^="filter"]').forEach(btn => {
            btn.style.borderColor = 'transparent';
            btn.classList.add('text-gray-600', 'dark:text-gray-400');
        });

        document.getElementById('filter' + status).style.borderColor = 'rgb(37, 99, 235)';
        document.getElementById('filter' + status).classList.remove('text-gray-600', 'dark:text-gray-400');

        // Re-render
        renderPreMatches();
    }

    function renderPreMatches() {
        let filtered = allPreMatches;

        if (currentFilter !== 'ALL') {
            filtered = filtered.filter(pm => pm.status === currentFilter);
        }

        console.log('Rendering', filtered.length, 'pre-matches');
        // Aquí irían los componentes renderizados dinámicamente
        // Por ahora, simplemente muestrar/ocultar con CSS
    }

    // Cargar castigos cuando la página carga
    document.addEventListener('DOMContentLoaded', function() {
        const groupId = document.body.dataset.groupId || {{ $group->id }};
        preMatchClient.loadPenalties(groupId).then(data => {
            if (data && data.penalties) {
                console.log('Castigos cargados:', data.penalties);
            }
        });
    });

    // Escuchar eventos de actualización en tiempo real
    window.addEventListener('pre-match:vote-update', function(e) {
        console.log('Vote update:', e.detail);
        // Aquí podrías auto-actualizar la UI
    });
</script>
@endsection
