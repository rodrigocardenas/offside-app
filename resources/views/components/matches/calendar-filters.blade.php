@php
    $themeMode = auth()->user()->theme_mode ?? 'light';
    $isDark = $themeMode === 'dark';
    
    $bgColor = $isDark ? '#1a524e' : '#f5f5f5';
    $textColor = $isDark ? '#f1fff8' : '#333333';
    $borderColor = $isDark ? '#2d7a77' : '#e0e0e0';
    $accentColor = '#00deb0';
@endphp

<div class="calendar-filters" style="padding: 16px; background: {{ $bgColor }}; border-bottom: 1px solid {{ $borderColor }};">
    
    {{-- SELECTOR DE COMPETENCIA --}}
    <div style="display: flex; flex-direction: column; gap: 8px;">
        <label style="font-size: 12px; font-weight: 700; color: {{ $textColor }}; text-transform: uppercase;">
            Filtrar por Liga
        </label>
        
        <div style="display: flex; gap: 8px; overflow-x: auto; padding-bottom: 8px;">
            {{-- Opción "Todas" --}}
            <button onclick="filterByCompetition(null)" 
                    id="filter-all"
                    class="filter-chip active"
                    style="padding: 8px 16px; background: linear-gradient(135deg, #17b796, {{ $accentColor }}); border: none; border-radius: 20px; color: white; font-weight: 600; font-size: 12px; cursor: pointer; white-space: nowrap; transition: all 0.3s;">
                Todas
            </button>

            {{-- Competencias disponibles --}}
            @foreach($competitions as $comp)
                <button onclick="filterByCompetition({{ $comp['id'] }})" 
                        id="filter-{{ $comp['id'] }}"
                        class="filter-chip"
                        style="padding: 8px 16px; background: {{ $isDark ? 'rgba(255,255,255,0.1)' : '#e8e8e8' }}; border: 2px solid transparent; border-radius: 20px; color: {{ $textColor }}; font-weight: 600; font-size: 12px; cursor: pointer; white-space: nowrap; transition: all 0.3s;"
                        onmouseover="this.style.borderColor='{{ $accentColor }}'"
                        onmouseout="this.style.borderColor='transparent'">
                    {{ $comp['name'] }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- RANGO DE FECHAS (OPCIONAL) --}}
    <div style="margin-top: 12px; display: flex; flex-direction: column; gap: 8px;">
        <label style="font-size: 12px; font-weight: 700; color: {{ $textColor }}; text-transform: uppercase;">
            Período
        </label>
        
        <div style="display: flex; gap: 8px;">
            <button onclick="setDateRange('week')" 
                    class="period-chip"
                    style="flex: 1; padding: 8px; background: {{ $isDark ? 'rgba(255,255,255,0.1)' : '#e8e8e8' }}; border: none; border-radius: 6px; color: {{ $textColor }}; font-weight: 600; font-size: 12px; cursor: pointer;">
                Esta Semana
            </button>
            <button onclick="setDateRange('month')" 
                    class="period-chip"
                    style="flex: 1; padding: 8px; background: {{ $isDark ? 'rgba(255,255,255,0.1)' : '#e8e8e8' }}; border: none; border-radius: 6px; color: {{ $textColor }}; font-weight: 600; font-size: 12px; cursor: pointer;">
                Este Mes
            </button>
        </div>
    </div>
</div>

<script>
    function filterByCompetition(competitionId) {
        // Remover clase active de todos los chips
        document.querySelectorAll('.filter-chip').forEach(chip => {
            chip.classList.remove('active');
        });
        
        // Agregar clase active al chip seleccionado
        if (competitionId === null) {
            document.getElementById('filter-all').classList.add('active');
        } else {
            document.getElementById('filter-' + competitionId).classList.add('active');
        }
        
        // Hacer request a la API para filtrar
        fetchMatches(competitionId);
    }

    function setDateRange(range) {
        // Hacer request a la API
        fetchMatches(null, range);
    }

    function fetchMatches(competitionId, range = 'week') {
        const url = new URL('/api/matches/calendar', window.location.origin);
        
        if (competitionId) {
            url.searchParams.append('competition_id', competitionId);
        }
        
        // Por ahora solo mostrar mensaje de carga
        console.log('Fetching matches:', { competitionId, range });
        // TODO: Implementar actual fetching
    }
</script>

<style>
    .filter-chip.active {
        background: linear-gradient(135deg, #17b796, {{ $accentColor }}) !important;
        color: white !important;
    }
</style>
