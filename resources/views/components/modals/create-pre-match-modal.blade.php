{{-- Modal para crear Pre Match --}}
<div id="createPreMatchModal" 
     style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 1rem;">
    
    <div style="background: {{ $isDark ? '#0f3d3a' : '#ffffff' }}; border-radius: 16px; width: 100%; max-width: 500px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 50px rgba(0,0,0,0.3);">
        
        <!-- Header -->
        <div style="padding: 24px; border-bottom: 1px solid {{ $borderColor }}; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; background: {{ $isDark ? '#0f3d3a' : '#ffffff' }}; z-index: 10;">
            <h2 style="font-size: 20px; font-weight: 700; color: {{ $textPrimary }}; margin: 0;">
                🔥 Crear Pre Match Challenge
            </h2>
            <button onclick="closeCreatePreMatchModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: {{ $textSecondary }}; padding: 0;">
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
                        style="width: 100%; padding: 12px; border: 1px solid {{ $borderColor }}; border-radius: 8px; background: {{ $isDark ? '#1a524e' : '#f5f5f5' }}; color: {{ $textPrimary }}; font-size: 14px; cursor: pointer;">
                    <option value="">-- Cargando partidos --</option>
                </select>
                <small style="display: block; margin-top: 8px; color: {{ $textSecondary }}; font-size: 12px;">
                    Only matches starting in the next 24 hours
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
                               style="display: block; text-align: center; padding: 12px 16px; border: 2px solid {{ $accentColor }}; border-radius: 8px; cursor: pointer; background: {{ $isDark ? 'rgba(0,222,176,0.15)' : '#e5f3f0' }}; color: {{ $accentColor }}; font-weight: 600; font-size: 13px; transition: all 0.2s ease;"
                               onchange="updatePenaltyUI()">
                            💰 Petar Puntos
                        </label>
                    </div>

                    <!-- CUSTOM PENALTY Option -->
                    <div style="flex: 1; min-width: 120px;">
                        <input type="radio" id="penaltyTypeCustom" name="penaltyType" value="CUSTOM_PENALTY" 
                               style="display: none;">
                        <label for="penaltyTypeCustom" 
                               style="display: block; text-align: center; padding: 12px 16px; border: 2px solid {{ $borderColor }}; border-radius: 8px; cursor: pointer; background: {{ $isDark ? '#1a524e' : '#f5f5f5' }}; color: {{ $textSecondary }}; font-weight: 600; font-size: 13px; transition: all 0.2s ease;"
                               onchange="updatePenaltyUI()">
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
                                onclick="selectPenaltyPoints(500)"
                                style="padding: 12px; border: 2px solid {{ $borderColor }}; border-radius: 8px; background: {{ $isDark ? '#1a524e' : '#f5f5f5' }}; color: {{ $textPrimary }}; font-weight: 600; cursor: pointer; font-size: 13px; transition: all 0.2s;">
                            -500
                        </button>
                        <button type="button" class="penalty-points-btn" data-points="1000"
                                onclick="selectPenaltyPoints(1000)"
                                style="padding: 12px; border: 2px solid {{ $borderColor }}; border-radius: 8px; background: {{ $isDark ? '#1a524e' : '#f5f5f5' }}; color: {{ $textPrimary }}; font-weight: 600; cursor: pointer; font-size: 13px; transition: all 0.2s;">
                            -1000
                        </button>
                        <button type="button" class="penalty-points-btn" data-points="2000"
                                onclick="selectPenaltyPoints(2000)"
                                style="padding: 12px; border: 2px solid {{ $borderColor }}; border-radius: 8px; background: {{ $isDark ? '#1a524e' : '#f5f5f5' }}; color: {{ $textPrimary }}; font-weight: 600; cursor: pointer; font-size: 13px; transition: all 0.2s;">
                            -2000
                        </button>
                        <button type="button" class="penalty-points-btn" data-points="ALL"
                                onclick="selectPenaltyPoints('ALL')"
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

<script>
    let preMatchGroupId = null;
    let selectedPenaltyPoints = 1000;

    function openCreatePreMatchModal(groupId) {
        preMatchGroupId = groupId;
        const modal = document.getElementById('createPreMatchModal');
        modal.style.display = 'flex';
        // Reset form
        document.getElementById('preMatchMatchSelect').value = '';
        document.getElementById('penaltyTypePoints').checked = true;
        document.getElementById('penaltyDescription').value = '';
        document.getElementById('charCount').textContent = '0';
        document.getElementById('preMatchError').style.display = 'none';
        updatePenaltyUI();
        selectPenaltyPoints(1000);
        loadAvailableMatches(groupId);
    }

    function closeCreatePreMatchModal() {
        document.getElementById('createPreMatchModal').style.display = 'none';
        document.getElementById('preMatchError').style.display = 'none';
    }

    function loadAvailableMatches(groupId) {
        const select = document.getElementById('preMatchMatchSelect');
        select.innerHTML = `<option value="">-- Cargando partidos próximos --</option>`;
        
        // Fetch upcoming matches from API
        fetch('/api/matches/upcoming', {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            }
        })
        .then(response => {
            if (!response.ok) throw new Error('Failed to load matches');
            return response.json();
        })
        .then(data => {
            // Handle both direct array and { data: [...] } formats
            const matches = Array.isArray(data) ? data : (data.data || []);
            
            if (!matches || matches.length === 0) {
                select.innerHTML = `<option value="">No hay partidos próximos disponibles</option>`;
                return;
            }
            
            select.innerHTML = '<option value="">-- Selecciona un partido --</option>' + matches.map(match => {
                const homeTeam = match.home_team?.name || match.home_team || 'Equipo A';
                const awayTeam = match.away_team?.name || match.away_team || 'Equipo B';
                const kickoffTime = match.kickoff_time ? new Date(match.kickoff_time).toLocaleString('es-AR', { 
                    month: 'short', 
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                }) : 'Hora TBD';
                return `<option value="${match.id}">${homeTeam} vs ${awayTeam} (${kickoffTime})</option>`;
            }).join('');
        })
        .catch(error => {
            console.error('Error loading matches:', error);
            select.innerHTML = `<option value="">Error al cargar partidos (${error.message})</option>`;
        });
    }
                return `<option value="${match.id}">${homeTeam} vs ${awayTeam} (${kickoffTime})</option>`;
            }).join('');
        })
        .catch(error => {
            console.error('Error loading matches:', error);
            select.innerHTML = `<option value="">Error al cargar partidos</option>`;
        });
    }

    function updatePenaltyUI() {
        const penaltyType = document.querySelector('input[name="penaltyType"]:checked').value;
        const pointsDetails = document.getElementById('pointsDetails');
        const customPenaltyDetails = document.getElementById('customPenaltyDetails');

        if (penaltyType === 'POINTS') {
            pointsDetails.style.display = 'block';
            customPenaltyDetails.style.display = 'none';
            // Update radio button styling
            document.querySelector('label[for="penaltyTypePoints"]').style.borderColor = '{{ $accentColor }}';
            document.querySelector('label[for="penaltyTypePoints"]').style.background = '{{ $isDark ? "rgba(0,222,176,0.15)" : "#e5f3f0" }}';
            document.querySelector('label[for="penaltyTypePoints"]').style.color = '{{ $accentColor }}';
            
            document.querySelector('label[for="penaltyTypeCustom"]').style.borderColor = '{{ $borderColor }}';
            document.querySelector('label[for="penaltyTypeCustom"]').style.background = '{{ $isDark ? "#1a524e" : "#f5f5f5" }}';
            document.querySelector('label[for="penaltyTypeCustom"]').style.color = '{{ $textSecondary }}';
        } else {
            pointsDetails.style.display = 'none';
            customPenaltyDetails.style.display = 'block';
            // Update radio button styling
            document.querySelector('label[for="penaltyTypePoints"]').style.borderColor = '{{ $borderColor }}';
            document.querySelector('label[for="penaltyTypePoints"]').style.background = '{{ $isDark ? "#1a524e" : "#f5f5f5" }}';
            document.querySelector('label[for="penaltyTypePoints"]').style.color = '{{ $textPrimary }}';
            
            document.querySelector('label[for="penaltyTypeCustom"]').style.borderColor = '{{ $accentColor }}';
            document.querySelector('label[for="penaltyTypeCustom"]').style.background = '{{ $isDark ? "rgba(0,222,176,0.15)" : "#e5f3f0" }}';
            document.querySelector('label[for="penaltyTypeCustom"]').style.color = '{{ $accentColor }}';
        }
    }

    function selectPenaltyPoints(points) {
        selectedPenaltyPoints = points;
        document.getElementById('selectedPointsText').textContent = points === 'ALL' ? '🔥 TODOS LOS PUNTOS' : '-' + points;
        
        // Update button styling
        document.querySelectorAll('.penalty-points-btn').forEach(btn => {
            if (btn.dataset.points == points) {
                btn.style.borderColor = '{{ $accentColor }}';
                btn.style.background = '{{ $isDark ? "rgba(0, 222, 176, 0.2)" : "#e5f3f0" }}';
                btn.style.color = '{{ $accentColor }}';
            } else {
                btn.style.borderColor = '{{ $borderColor }}';
                btn.style.background = '{{ $isDark ? "#1a524e" : "#f5f5f5" }}';
                btn.style.color = '{{ $textPrimary }}';
            }
        });
    }

    document.getElementById('penaltyDescription')?.addEventListener('input', function() {
        document.getElementById('charCount').textContent = this.value.length;
    });

    function submitCreatePreMatch() {
        const matchId = document.getElementById('preMatchMatchSelect').value;
        const penaltyType = document.querySelector('input[name="penaltyType"]:checked').value;
        const penaltyDescription = document.getElementById('penaltyDescription').value;
        const errorDiv = document.getElementById('preMatchError');

        // Validation
        if (!matchId) {
            showError('Por favor selecciona un partido');
            return;
        }

        if (penaltyType === 'CUSTOM_PENALTY') {
            if (!penaltyDescription.trim()) {
                showError('Describe el castigo personalizado');
                return;
            }
        }

        const payload = {
            match_id: parseInt(matchId),
            group_id: preMatchGroupId,
            penalty_type: penaltyType,
            penalty_points: penaltyType === 'POINTS' ? (selectedPenaltyPoints === 'ALL' ? 9999 : parseInt(selectedPenaltyPoints)) : null,
            penalty_description: penaltyType === 'CUSTOM_PENALTY' ? penaltyDescription : null,
        };

        // Submit via AJAX
        fetch(`/api/pre-matches`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload),
        })
        .then(response => {
            if (!response.ok) throw new Error(response.statusText);
            return response.json();
        })
        .then(data => {
            // Success - show notification and reload
            showNotification('✅ Pre Match creado exitosamente', 'success');
            closeCreatePreMatchModal();
            setTimeout(() => location.reload(), 1500);
        })
        .catch(error => {
            showError('Error: ' + error.message);
        });
    }

    function showError(message) {
        const errorDiv = document.getElementById('preMatchError');
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
    }

    function showNotification(message, type = 'info') {
        // Use existing notification system if available
        if (window.showNotification) {
            window.showNotification(message, type);
        } else {
            alert(message);
        }
    }

    // Initialize on modal open
    updatePenaltyUI();
    selectPenaltyPoints(1000);
</script>

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
