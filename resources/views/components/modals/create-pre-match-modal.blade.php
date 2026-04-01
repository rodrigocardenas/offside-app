{{-- Create Pre Match Modal Component --}}
{{-- jQuery & Select2 are now loaded globally in app.blade.php --}}

<div id="createPreMatchModal"
     style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 1rem;">

    <div style="background: {{ $isDark ? '#0f3d3a' : '#ffffff' }}; border-radius: 16px; width: 100%; max-width: 500px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 50px rgba(0,0,0,0.3);">

        <!-- Header -->
        <div style="padding: 24px; border-bottom: 1px solid {{ $borderColor }}; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; background: {{ $isDark ? '#0f3d3a' : '#ffffff' }}; z-index: 10;">
            <h2 style="font-size: 20px; font-weight: 700; color: {{ $textPrimary }}; margin: 0;">
                🔥 Crear Pre Match Challenge
            </h2>
            <button type="button" onclick="closeCreatePreMatchModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: {{ $textSecondary }}; padding: 0;">
                ✕
            </button>
        </div>

        <!-- Body -->
        <div style="padding: 24px; space-y: 20px;">

            <!-- Step 1: Seleccionar Partido -->
            <div style="margin-bottom: 24px;">
                <label style="display: block; font-weight: 700; font-size: 14px; margin-bottom: 12px; color: {{ $textPrimary }};">
                    📅 Selecciona un Partido
                </label>
                
                <!-- Search Input -->
                <input type="text" 
                       id="preMatchSearchInput"
                       placeholder="🔍 Busca un partido (equipo, competencia, fecha)..."
                       style="width: 100%; padding: 12px; border: 1px solid {{ $borderColor }}; border-radius: 8px; background: {{ $isDark ? '#1a524e' : '#f5f5f5' }}; color: {{ $textPrimary }}; font-size: 14px; margin-bottom: 8px;">
                
                <!-- Results Dropdown -->
                <div id="preMatchSearchResults" 
                     style="display: none; position: absolute; background: {{ $isDark ? '#1a524e' : '#f5f5f5' }}; border: 1px solid {{ $borderColor }}; border-radius: 8px; max-height: 200px; overflow-y: auto; z-index: 100; width: 460px; margin-top: -8px; padding: 4px 0;">
                </div>
                
                <!-- Hidden input for storing match_id -->
                <input type="hidden" id="preMatchMatchSelect" value="" />
                
                <!-- Selected Match Display -->
                <div id="selectedMatchDisplay" style="display: none; padding: 12px; border: 1px solid {{ $accentColor }}; border-radius: 8px; background: {{ $isDark ? 'rgba(0,222,176,0.1)' : '#e5f3f0' }}; color: {{ $textPrimary }}; font-size: 13px; font-weight: 600; margin-top: 8px;"></div>
                
                <small style="display: block; margin-top: 8px; color: {{ $textSecondary }}; font-size: 12px;">
                    Solo partidos en los próximos 7 días
                </small>
            </div>

            <!-- Step 2: Tipo de Penalización -->
            <div style="margin-bottom: 24px;">
                <label style="display: block; font-weight: 700; font-size: 14px; margin-bottom: 12px; color: {{ $textPrimary }};">
                    🔥 Tipo de Castigo
                </label>
                <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                    <!-- POINTS Option -->
                    <div style="flex: 1; min-width: 120px;">
                        <input type="radio" id="penaltyTypePoints" name="penaltyType" value="POINTS" checked
                               style="display: none;">
                        <label for="penaltyTypePoints"
                               class="penalty-type-label"
                               data-type="POINTS"
                               style="display: block; text-align: center; padding: 12px 16px; border: 2px solid {{ $accentColor }}; border-radius: 8px; cursor: pointer; background: {{ $isDark ? 'rgba(0,222,176,0.15)' : '#e5f3f0' }}; color: {{ $accentColor }}; font-weight: 600; font-size: 13px; transition: all 0.2s ease;">
                            💰 Petar Puntos
                        </label>
                    </div>

                    <!-- SOCIAL PENALTY Option -->
                    <div style="flex: 1; min-width: 120px;">
                        <input type="radio" id="penaltyTypeCustom" name="penaltyType" value="SOCIAL"
                               style="display: none;">
                        <label for="penaltyTypeCustom"
                               class="penalty-type-label"
                               data-type="SOCIAL"
                               style="display: block; text-align: center; padding: 12px 16px; border: 2px solid {{ $borderColor }}; border-radius: 8px; cursor: pointer; background: {{ $isDark ? '#1a524e' : '#f5f5f5' }}; color: {{ $textSecondary }}; font-weight: 600; font-size: 13px; transition: all 0.2s ease;">
                            📝 Castigo Personalizado
                        </label>
                    </div>
                </div>
            </div>

            <!-- Step 3: Detalles del Castigo -->
            <div id="penaltyDetailsContainer" style="margin-bottom: 24px;">
                <!-- POINTS Details -->
                <div id="pointsDetails" style="display: block;">
                    <label style="display: block; font-weight: 700; font-size: 14px; margin-bottom: 12px; color: {{ $textPrimary }};">
                        Puntos a Restar
                    </label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 8px;">
                        <button type="button" class="penalty-points-btn" data-points="500"
                                style="padding: 12px; border: 2px solid {{ $borderColor }}; border-radius: 8px; background: {{ $isDark ? '#1a524e' : '#f5f5f5' }}; color: {{ $textPrimary }}; font-weight: 600; cursor: pointer; font-size: 13px; transition: all 0.2s;">
                            -500
                        </button>
                        <button type="button" class="penalty-points-btn" data-points="1000"
                                style="padding: 12px; border: 2px solid {{ $accentColor }}; border-radius: 8px; background: {{ $isDark ? 'rgba(0,222,176,0.15)' : '#e5f3f0' }}; color: {{ $textPrimary }}; font-weight: 600; cursor: pointer; font-size: 13px; transition: all 0.2s;"
                                data-selected="true">
                            -1000
                        </button>
                        <button type="button" class="penalty-points-btn" data-points="2000"
                                style="padding: 12px; border: 2px solid {{ $borderColor }}; border-radius: 8px; background: {{ $isDark ? '#1a524e' : '#f5f5f5' }}; color: {{ $textPrimary }}; font-weight: 600; cursor: pointer; font-size: 13px; transition: all 0.2s;">
                            -2000
                        </button>
                        <button type="button" class="penalty-points-btn" data-points="ALL"
                                style="padding: 12px; border: 2px solid {{ $borderColor }}; border-radius: 8px; background: {{ $isDark ? '#1a524e' : '#f5f5f5' }}; color: {{ $textPrimary }}; font-weight: 600; cursor: pointer; font-size: 13px; transition: all 0.2s;">
                            🔥 TODOS
                        </button>
                    </div>
                    <small style="display: block; margin-top: 8px; color: {{ $textSecondary }}; font-size: 12px;">
                        Seleccionado: <strong id="selectedPointsText">-1000</strong>
                    </small>
                </div>

                <!-- SOCIAL PENALTY Details -->
                <div id="customPenaltyDetails" style="display: none;">
                    <label style="display: block; font-weight: 700; font-size: 14px; margin-bottom: 12px; color: {{ $textPrimary }};">
                        📝 Describe el Castigo
                    </label>
                    <textarea id="penaltyDescription"
                              placeholder="Ej: Pagar cena para todos, video vergüenza, reto futuro, lavar camisetas, etc."
                              style="width: 100%; padding: 12px; border: 1px solid {{ $borderColor }}; border-radius: 8px; background: {{ $isDark ? '#1a524e' : '#f5f5f5' }}; color: {{ $textPrimary }}; font-size: 14px; font-family: inherit; resize: vertical; min-height: 100px;"
                              maxlength="500"></textarea>
                    <small style="display: block; margin-top: 8px; color: {{ $textSecondary }}; font-size: 12px;">
                        <span id="charCount">0</span>/500 caracteres
                    </small>
                </div>
            </div>

            <!-- Info Box -->
            <div style="padding: 16px; background: {{ $isDark ? '#1a524e' : '#e5f3f0' }}; border: 1px solid {{ $accentColor }}; border-radius: 8px; margin-bottom: 24px;">
                <p style="margin: 0; font-size: 12px; color: {{ $textPrimary }}; line-height: 1.6;">
                    <strong>📋 Cómo funciona:</strong><br>
                    1. Todos los miembros pueden proponer acciones improbables<br>
                    2. El grupo vota cada propuesta (✅ Es posible / ❌ Muy extremo)<br>
                    3. Después del partido, el admin valida si ocurrió<br>
                    4. Si ocurrió → se aplica automáticamente el castigo
                </p>
            </div>

            <!-- Error Message -->
            <div id="preMatchError"
                 style="display: none; padding: 12px; background: #ffebee; border: 1px solid #ef5350; border-radius: 8px; color: #c62828; font-size: 13px; margin-bottom: 16px;">
            </div>

            <!-- Buttons -->
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" onclick="closeCreatePreMatchModal()"
                        style="padding: 12px 24px; border: 1px solid {{ $borderColor }}; border-radius: 8px; background: {{ $isDark ? '#1a524e' : '#f5f5f5' }}; color: {{ $textPrimary }}; font-weight: 600; cursor: pointer; font-size: 14px; transition: all 0.2s;">
                    Cancelar
                </button>
                <button type="button" id="createPreMatchBtn" onclick="submitCreatePreMatch()"
                        style="padding: 12px 24px; border: none; border-radius: 8px; background: linear-gradient(135deg, #ff6b6b, #ff8787); color: #fff; font-weight: 600; cursor: pointer; font-size: 14px; transition: all 0.2s;"
                        onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 10px 20px rgba(255, 107, 107, 0.25)';"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                    🚀 Crear Pre Match
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    #createPreMatchModal {
        animation: slideUp 0.3s ease;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<!-- JavaScript Module for Modal Management -->
<script>
(function() {
    // Theme configuration from Blade
    const THEME = {
        isDark: {{ json_encode($isDark) }},
        accentColor: '{{ $accentColor }}',
        borderColor: '{{ $borderColor }}',
        textPrimary: '{{ $textPrimary }}',
        textSecondary: '{{ $textSecondary }}',
        bgPrimary: '{{ $bgPrimary }}',
        bgSecondary: '{{ $bgSecondary }}',
        bgTertiary: '{{ $bgTertiary }}'
    };

    // Global state
    let preMatchGroupId = null;
    let selectedPenaltyPoints = 1000;

    // Export functions to window
    window.openCreatePreMatchModal = function(groupId) {
        console.log('🎬 openCreatePreMatchModal called with groupId:', groupId);
        preMatchGroupId = groupId;
        const modal = document.getElementById('createPreMatchModal');
        if (modal) {
            modal.style.display = 'flex';
            document.getElementById('preMatchSearchInput').value = '';
            document.getElementById('preMatchMatchSelect').value = '';
            document.getElementById('selectedMatchDisplay').style.display = 'none';
            document.getElementById('preMatchSearchResults').style.display = 'none';
            document.getElementById('penaltyTypePoints').checked = true;
            document.getElementById('preMatchError').style.display = 'none';
            selectedPenaltyPoints = 1000;
            updatePenaltyUI();
            // Load matches FIRST, then initialize search after matches are loaded
            loadUpcomingMatches().then(() => {
                initializeMatchSearch();
            });
        } else {
            console.error('❌ Modal element not found');
        }
    };

    window.closeCreatePreMatchModal = function() {
        const modal = document.getElementById('createPreMatchModal');
        if (modal) {
            modal.style.display = 'none';
        }
    };

    function loadUpcomingMatches() {
        const searchInput = document.getElementById('preMatchSearchInput');
        if (!searchInput) {
            console.warn('⚠️ Search input element not found');
            return Promise.reject('Search input not found');
        }

        console.log('📥 Loading upcoming matches...');
        searchInput.placeholder = 'Cargando partidos...';
        searchInput.disabled = true;

        // Return the promise so caller can wait for completion
        return fetch('/api/matches/upcoming')
            .then(r => {
                console.log('✅ Matches API response status:', r.status);
                if (!r.ok) throw new Error(`API Error ${r.status}`);
                return r.json();
            })
            .then(response => {
                const matches = Array.isArray(response) ? response : (response.data || []);
                console.log('📊 Parsed matches count:', matches.length);
                
                if (matches.length === 0) {
                    searchInput.placeholder = 'No hay partidos disponibles';
                    return;
                }

                // Store matches in window for search
                window.preMatchesData = matches;
                console.log('✅ Stored matches in window.preMatchesData:', window.preMatchesData.length);
                searchInput.placeholder = '🔍 Busca un partido (equipo, competencia, fecha)...';
                searchInput.disabled = false;
                searchInput.focus();

                console.log('✅ Matches loaded successfully');
            })
            .catch(err => {
                console.error('❌ Error loading matches:', err);
                searchInput.placeholder = 'Error al cargar partidos';
                searchInput.disabled = true;
                throw err;
            });
    }

    function initializeMatchSearch() {
        const searchInput = document.getElementById('preMatchSearchInput');
        const resultsDiv = document.getElementById('preMatchSearchResults');
        const selectedDisplay = document.getElementById('selectedMatchDisplay');
        const hiddenSelect = document.getElementById('preMatchMatchSelect');

        if (!searchInput || !resultsDiv) {
            console.warn('⚠️ Search elements not found');
            return;
        }

        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            
            if (!query) {
                resultsDiv.style.display = 'none';
                return;
            }

            const matches = window.preMatchesData || [];
            const filtered = matches.filter(m => {
                const home = m.home_team?.name || '';
                const away = m.away_team?.name || '';
                const comp = m.competition?.name || '';
                const searchText = `${home} ${away} ${comp} ${m.kick_off_time || ''}`.toLowerCase();
                return searchText.includes(query);
            });

            console.log('🔍 Filtered matches:', filtered.length);

            if (filtered.length === 0) {
                resultsDiv.innerHTML = '<div style="padding: 12px; color: #999;">No se encontraron partidos</div>';
                resultsDiv.style.display = 'block';
                return;
            }

            resultsDiv.innerHTML = filtered.map(match => `
                <div class="match-option" 
                     data-match-id="${match.id}"
                     data-home-team="${(match.home_team?.name || 'Equipo A').replace(/"/g, '&quot;')}"
                     data-away-team="${(match.away_team?.name || 'Equipo B').replace(/"/g, '&quot;')}"
                     data-kick-off="${match.kick_off_time || 'TBD'}"
                     style="padding: 10px 12px; cursor: pointer; border-bottom: 1px solid {{ $borderColor }}; color: {{ $textPrimary }}; font-size: 13px; transition: background 0.2s ease;"
                     onmouseover="this.style.background='{{ $isDark ? '#2a4a47' : '#e5f3f0' }}'"
                     onmouseout="this.style.background='transparent'"
                     onclick="selectMatchFromDropdown(this)">
                    <strong>${match.home_team?.name || 'Equipo A'} vs ${match.away_team?.name || 'Equipo B'}</strong><br>
                    ${match.kick_off_time || 'Hora TBD'} · ${match.competition?.name || 'Competencia'}
                </div>
            `).join('');

            resultsDiv.style.display = 'block';
        });

        // Close results when clicking outside
        document.addEventListener('click', function(e) {
            if (e.target !== searchInput && !e.target.closest('.match-option')) {
                resultsDiv.style.display = 'none';
            }
        });
    }

    function updatePenaltyUI() {
        console.log('🎯 updatePenaltyUI called');
        const penaltyType = document.querySelector('input[name="penaltyType"]:checked');

        if (!penaltyType) {
            console.warn('⚠️ No checked radio button found');
            return;
        }

        const selectedValue = penaltyType.value;
        console.log('✅ Selected penalty type:', selectedValue);

        const pointsDetails = document.getElementById('pointsDetails');
        const customDetails = document.getElementById('customPenaltyDetails');
        const labels = document.querySelectorAll('.penalty-type-label');

        // Hide/show details sections
        if (selectedValue === 'POINTS') {
            if (pointsDetails) pointsDetails.style.display = 'block';
            if (customDetails) customDetails.style.display = 'none';
        } else if (selectedValue === 'SOCIAL') {
            if (pointsDetails) pointsDetails.style.display = 'none';
            if (customDetails) customDetails.style.display = 'block';
        }

        // Update label styles
        labels.forEach(label => {
            const labelType = label.getAttribute('data-type');
            if (labelType === selectedValue) {
                // Highlight selected
                label.style.borderColor = THEME.accentColor;
                label.style.background = THEME.isDark ? 'rgba(0,222,176,0.3)' : '#d4f0ed';
                label.style.color = THEME.accentColor;
                label.style.borderWidth = '2px';
            } else {
                // Reset unselected
                label.style.borderColor = THEME.borderColor;
                label.style.background = THEME.isDark ? '#1a524e' : '#f5f5f5';
                label.style.color = THEME.textSecondary;
                label.style.borderWidth = '2px';
            }
        });
    }

    window.updatePenaltyUI = updatePenaltyUI;

    window.selectPenaltyPoints = function(points) {
        selectedPenaltyPoints = points;
        const txt = document.getElementById('selectedPointsText');
        if (txt) {
            txt.textContent = points === 'ALL' ? '🔥 TODOS' : '-' + points;
        }

        // Update button styles
        document.querySelectorAll('.penalty-points-btn').forEach(btn => {
            const btnPoints = btn.getAttribute('data-points');
            btn.style.borderColor = (btnPoints == points) ? THEME.accentColor : THEME.borderColor;
            btn.style.background = (btnPoints == points)
                ? (THEME.isDark ? 'rgba(0,222,176,0.15)' : '#e5f3f0')
                : (THEME.isDark ? '#1a524e' : '#f5f5f5');
        });
    };

    window.selectMatchFromDropdown = function(element) {
        console.log('=== selectMatchFromDropdown called ===');
        console.log('Element:', element);
        console.log('Element type:', typeof element);
        
        const matchId = element.getAttribute('data-match-id');
        const homeTeam = element.getAttribute('data-home-team');
        const awayTeam = element.getAttribute('data-away-team');
        const kickOff = element.getAttribute('data-kick-off');
        
        console.log('Extracted values:', {matchId, homeTeam, awayTeam, kickOff});
        console.log('✅ selectMatchFromDropdown called with matchId:', matchId);
        
        const searchInput = document.getElementById('preMatchSearchInput');
        const resultsDiv = document.getElementById('preMatchSearchResults');
        const selectedDisplay = document.getElementById('selectedMatchDisplay');
        const hiddenSelect = document.getElementById('preMatchMatchSelect');
        
        console.log('Elements found:', {
            searchInput: !!searchInput,
            resultsDiv: !!resultsDiv,
            selectedDisplay: !!selectedDisplay,
            hiddenSelect: !!hiddenSelect
        });
        
        if (hiddenSelect && searchInput && selectedDisplay && resultsDiv) {
            hiddenSelect.value = matchId;
            searchInput.value = `${homeTeam} vs ${awayTeam}`;
            selectedDisplay.textContent = `✅ ${homeTeam} vs ${awayTeam} (${kickOff})`;
            selectedDisplay.style.display = 'block';
            resultsDiv.style.display = 'none';
            console.log('✅ Match stored - hiddenSelect.value is now:', hiddenSelect.value);
            console.log('Element value attr:', hiddenSelect.getAttribute('value'));
            console.log('All options in select:', hiddenSelect.innerHTML);
        } else {
            console.error('❌ One or more elements not found!');
        }
    };

    window.submitCreatePreMatch = function() {
        console.log('=== submitCreatePreMatch called ===');
        const matchSelect = document.getElementById('preMatchMatchSelect');
        const penaltyType = document.querySelector('input[name="penaltyType"]:checked');
        const customPenalty = document.getElementById('penaltyDescription');

        console.log('Form elements check:', {
            matchSelect: !!matchSelect,
            matchSelectId: matchSelect?.id,
            matchSelectValue: matchSelect?.value,
            matchSelectValueType: typeof matchSelect?.value,
            matchSelectValueLength: matchSelect?.value?.length,
            penaltyType: !!penaltyType,
            customPenalty: !!customPenalty
        });

        if (!matchSelect || !penaltyType) {
            alert('Error: Elementos del formulario no encontrados');
            return;
        }

        const matchId = matchSelect.value;
        console.log('matchId extracted:', matchId);
        console.log('matchId is truthy:', !!matchId);
        console.log('matchId length:', matchId?.length);
        console.log('matchId === "":', matchId === "");
        console.log('!matchId:', !matchId);
        
        if (!matchId) {
            alert('Por favor selecciona un partido');
            console.error('❌ Empty matchId, showing alert');
            return;
        }

        if (penaltyType.value === 'SOCIAL' && !customPenalty?.value?.trim()) {
            alert('Por favor describe el castigo personalizado');
            return;
        }

        const penaltyValue = penaltyType.value === 'POINTS'
            ? `-${selectedPenaltyPoints} puntos`
            : customPenalty.value;

        const payload = {
            football_match_id: parseInt(matchId),
            group_id: parseInt(preMatchGroupId),
            penalty_type: penaltyType.value,
            penalty_points: penaltyType.value === 'POINTS' ? (selectedPenaltyPoints === 'ALL' ? 5000 : parseInt(selectedPenaltyPoints)) : null,
            penalty_description: penaltyType.value === 'SOCIAL' ? customPenalty.value : null
        };

        fetch(`/api/pre-matches`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(payload)
        })
        .then(r => {
            console.log('Response status:', r.status);
            console.log('Response headers:', {
                'content-type': r.headers.get('content-type')
            });
            
            // Try to get response as text first to debug
            return r.text().then(text => {
                console.log('Raw response text (first 200 chars):', text.substring(0, 200));
                
                if (!r.ok) {
                    throw new Error(`API Error ${r.status}: ${text.substring(0, 500)}`);
                }
                
                // Try to parse as JSON
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error(`Invalid JSON response: ${e.message}\nResponse: ${text.substring(0, 500)}`);
                }
            });
        })
        .then(data => {
            console.log('✅ Success response:', data);
            alert('✅ Pre Match creado exitosamente!');
            window.closeCreatePreMatchModal();
            setTimeout(() => location.reload(), 1000);
        })
        .catch(err => {
            console.error('❌ Error:', err);
            console.error('Error message:', err.message);
            alert('❌ Error: ' + err.message);
        });
    };

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🚀 DOMContentLoaded: Initializing modal');

        // Character counter
        const textarea = document.getElementById('penaltyDescription');
        if (textarea) {
            textarea.addEventListener('input', function() {
                const count = document.getElementById('charCount');
                if (count) count.textContent = this.value.length;
            });
        }

        // Radio button change handlers
        const radioButtons = document.querySelectorAll('input[name="penaltyType"]');
        radioButtons.forEach(radio => {
            radio.addEventListener('change', function() {
                console.log('📻 Radio changed:', this.value);
                updatePenaltyUI();
            });
        });

        // Penalty points button handlers
        document.querySelectorAll('.penalty-points-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const points = this.getAttribute('data-points');
                console.log('💰 Points selected:', points);
                window.selectPenaltyPoints(points);
            });
        });

        console.log('✅ Modal initialization complete');
    });
})();
</script>
