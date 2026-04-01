{{-- Create Pre Match Modal Component --}}
<!-- jQuery & Select2 Dependencies -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2-bootstrap-5-theme/1.3.0/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

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
                <select id="preMatchMatchSelect"
                        class="w-full select2-match-selector"
                        data-placeholder="🔍 Busca un partido (equipo, competencia, fecha)..."
                        style="width: 100%; padding: 12px; border: 1px solid {{ $borderColor }}; border-radius: 8px; background: {{ $isDark ? '#1a524e' : '#f5f5f5' }}; color: {{ $textPrimary }}; font-size: 14px; cursor: pointer;">
                    <option value="">-- Cargando partidos --</option>
                </select>
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

                    <!-- CUSTOM PENALTY Option -->
                    <div style="flex: 1; min-width: 120px;">
                        <input type="radio" id="penaltyTypeCustom" name="penaltyType" value="CUSTOM_PENALTY"
                               style="display: none;">
                        <label for="penaltyTypeCustom"
                               class="penalty-type-label"
                               data-type="CUSTOM_PENALTY"
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

                <!-- CUSTOM PENALTY Details -->
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
            document.getElementById('preMatchMatchSelect').value = '';
            document.getElementById('penaltyTypePoints').checked = true;
            document.getElementById('preMatchError').style.display = 'none';
            selectedPenaltyPoints = 1000;
            updatePenaltyUI();
            loadUpcomingMatches();
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
        const select = document.getElementById('preMatchMatchSelect');
        if (!select) {
            console.warn('⚠️ Match select element not found');
            return;
        }

        select.innerHTML = '<option value="">Cargando partidos...</option>';

        fetch('/api/matches/upcoming')
            .then(r => r.json())
            .then(response => {
                select.innerHTML = '<option value="">-- Selecciona un partido --</option>';
                // Handle both direct array (legacy) and API response format
                const matches = Array.isArray(response) ? response : (response.data || []);
                if (Array.isArray(matches) && matches.length > 0) {
                    matches.forEach(match => {
                        // Convert Unix timestamp (seconds) to milliseconds for JavaScript Date
                        const kickoffMs = (match.kick_off_timestamp || 0) * 1000;
                        const date = new Date(kickoffMs).toLocaleDateString('es-ES');
                        // Use the pre-formatted time from API
                        const time = match.kick_off_time || new Date(kickoffMs).toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit'});
                        const homeTeam = match.home_team?.name || 'Equipo A';
                        const awayTeam = match.away_team?.name || 'Equipo B';
                        const competition = match.competition?.name ? ` [${match.competition.name}]` : '';
                        const option = document.createElement('option');
                        option.value = match.id;
                        option.textContent = `${homeTeam} vs ${awayTeam} - ${time}${competition}`;
                        select.appendChild(option);
                    });
                } else {
                    select.innerHTML = '<option value="">No hay partidos disponibles</option>';
                }

                // Initialize or update Select2
                initializeSelect2();
            })
            .catch(err => {
                console.error('❌ Error loading matches:', err);
                select.innerHTML = '<option value="">Error al cargar partidos</option>';
                initializeSelect2();
            });
    }

    function initializeSelect2() {
        const select = document.getElementById('preMatchMatchSelect');
        if (!select) return;

        // Destroy existing Select2 instance if it exists
        if ($.fn.select2 && select.classList.contains('select2-hidden-accessible')) {
            $('#preMatchMatchSelect').select2('destroy');
        }

        // Initialize Select2 with custom styling
        if ($ && $.fn.select2) {
            $('#preMatchMatchSelect').select2({
                allowClear: true,
                placeholder: '🔍 Busca un partido (equipo, competencia, fecha)...',
                width: '100%',
                language: {
                    noResults: function () {
                        return 'No se encontraron partidos';
                    },
                    searching: function () {
                        return 'Buscando...';
                    }
                },
                templateResult: function(data) {
                    if (!data.id) return data.text;
                    return $('<span>' + data.text + '</span>');
                },
                templateSelection: function(data) {
                    if (!data.id) return data.text;
                    return $('<span>' + data.text + '</span>');
                }
            });

            // Custom styling for dark mode
            const modal = document.getElementById('createPreMatchModal');
            if (THEME.isDark) {
                const select2Container = $('#preMatchMatchSelect').parent().find('.select2-container');
                select2Container.addClass('dark-mode-select2');
            }
        } else {
            console.warn('⚠️ jQuery or Select2 not yet loaded');
            setTimeout(initializeSelect2, 100);
        }
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
        } else if (selectedValue === 'CUSTOM_PENALTY') {
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

    window.submitCreatePreMatch = function() {
        const matchSelect = document.getElementById('preMatchMatchSelect');
        const penaltyType = document.querySelector('input[name="penaltyType"]:checked');
        const customPenalty = document.getElementById('penaltyDescription');

        if (!matchSelect || !penaltyType) {
            alert('Error: Elementos del formulario no encontrados');
            return;
        }

        const matchId = matchSelect.value;
        if (!matchId) {
            alert('Por favor selecciona un partido');
            return;
        }

        if (penaltyType.value === 'CUSTOM_PENALTY' && !customPenalty?.value?.trim()) {
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
            penalty_description: penaltyType.value === 'CUSTOM_PENALTY' ? customPenalty.value : null
        };

        fetch(`/api/pre-matches`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                alert('❌ Error: ' + data.error);
            } else {
                alert('✅ Pre Match creado exitosamente!');
                window.closeCreatePreMatchModal();
                setTimeout(() => location.reload(), 1000);
            }
        })
        .catch(err => {
            console.error('❌ Error:', err);
            alert('Error al crear Pre Match');
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
