<x-app-layout
    :logo-url="asset('images/logo_alone.png')"
    alt-text="Offside Club"
>
    @section('navigation-title', 'Pre Match - ' . $preMatch->match->home_team . ' vs ' . $preMatch->match->away_team)

    @php
        $themeMode = auth()->user()->theme_mode ?? 'light';
        $isDark = $themeMode === 'dark';

        // Colores dinámicos
        $bgPrimary = $isDark ? '#0a2e2c' : '#f5f5f5';
        $bgSecondary = $isDark ? '#0f3d3a' : '#f5f5f5';
        $bgTertiary = $isDark ? '#1a524e' : '#ffffff';
        $textPrimary = $isDark ? '#ffffff' : '#333333';
        $textSecondary = $isDark ? '#b0b0b0' : '#999999';
        $borderColor = $isDark ? '#2a4a47' : '#e0e0e0';
        $accentColor = '#00deb0';
        $accentDark = '#17b796';
        $accentLight = 'rgba(0, 222, 176, 0.1)';
        $redAccent = '#ff6b6b';
        $redLight = 'rgba(255, 107, 107, 0.1)';
        $greenAccent = '#4CAF50';

        // Colores del header según el estado
        $headerGradient = 'linear-gradient(135deg, #ff6b6b, #ff8787)'; // pending - rojo
        $headerStatus = $preMatch->status;
        $headerStatusLabel = 'Pendiente';

        if ($preMatch->status === 'active') {
            $headerGradient = 'linear-gradient(135deg, #ffa726, #ffb74d)'; // active - naranja
            $headerStatusLabel = '🔴 Activo';
        } elseif ($preMatch->status === 'completed') {
            $headerGradient = 'linear-gradient(135deg, #66bb6a, #81c784)'; // completed - verde
            $headerStatusLabel = '✅ Completado';
        } else {
            $headerStatusLabel = '⏳ Pendiente';
        }
    @endphp

    <div class="min-h-screen p-1 md:p-6 pb-24" style="background: {{ $bgPrimary }}; color: {{ $textPrimary }}; margin-top: 3.75rem;">

        <!-- Back Button & Header -->
        <div class="ml-1 mr-1 mb-6">
            <a href="{{ route('groups.pre-matches', $group) }}"
               style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; border: none; border-radius: 8px; background: {{ $isDark ? '#2a4a47' : '#e5f3f0' }}; color: {{ $accentColor }}; font-size: 13px; font-weight: 600; cursor: pointer; border: 1px solid {{ $borderColor }}; transition: all 0.2s ease; text-decoration: none; margin-bottom: 16px;"
               onmouseover="this.style.background='{{ $accentLight }}'"
               onmouseout="this.style.background='{{ $isDark ? '#2a4a47' : '#e5f3f0' }}';">
                ← Volver a Desafíos
            </a>
        </div>

        <!-- Match Card Header -->
        <div class="ml-1 mr-1" style="background: {{ $headerGradient }}; border-radius: 16px; padding: 16px; margin-bottom: 24px; color: #fff;">
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <div>
                    <p style="font-size: 11px; font-weight: 700; text-transform: uppercase; opacity: 0.9; margin: 0 0 8px 0;">
                        🔥 Pre Match Challenge
                    </p>
                    <h1 style="font-size: 20px; font-weight: 700; margin: 0 0 8px 0; word-break: break-word;">
                        {{ $preMatch->match->home_team }} vs {{ $preMatch->match->away_team }}
                    </h1>
                    <p style="font-size: 12px; opacity: 0.95; margin: 0;">
                        ⏰ {{ $preMatch->match->date?->format('d/m/Y H:i') ?? 'TBD' }}
                    </p>
                </div>
                <div style="display: flex; gap: 12px; align-items: center; justify-content: space-between;">
                    <span style="display: inline-block; padding: 6px 12px; background: rgba(255,255,255,0.2); border-radius: 8px; font-size: 11px; font-weight: 700;">
                        {{ $headerStatusLabel }}
                    </span>
                    <p style="font-size: 11px; margin: 0; opacity: 0.8;">
                        Creado por {{ $preMatch->creator->name }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Content Grid -->
        <div id="contentGrid" style="display: grid; grid-template-columns: 1fr; gap: 16px; margin-bottom: 24px; padding: 0 1rem;">

            <!-- Main Content (Propositions) -->
            <div>
                <!-- Challenge Details -->
                <div style="background: {{ $bgTertiary }}; padding: 16px; border-radius: 12px; border: 1px solid {{ $borderColor }}; margin-bottom: 16px;">
                    <h2 style="font-size: 16px; font-weight: 700; color: {{ $textPrimary }}; margin: 0 0 12px 0;">
                        💣 Consecuencias sí se cumple tu propuesta:
                    </h2>

                    <div style="padding: 12px; border-radius: 8px;
                        @if($preMatch->penalty_type === 'POINTS')
                            background: {{ $redLight }}; border-left: 4px solid {{ $redAccent }};
                        @else
                            background: rgba(255, 149, 0, 0.1); border-left: 4px solid #ff9500;
                        @endif
                    ">
                        @if($preMatch->penalty_type === 'POINTS')
                            <p style="color: {{ $redAccent }}; font-weight: 700; margin: 0; font-size: 14px;">
                                💔 Restar {{ $preMatch->penalty_points }} puntos
                            </p>
                        @else
                            <p style="color: #ff9500; font-weight: 700; margin: 0; font-size: 14px;">
                                📝 {{ $preMatch->penalty_description }}
                            </p>
                        @endif
                    </div>
                </div>

                <!-- Propositions Section -->
                <div style="background: {{ $bgTertiary }}; padding: 16px; border-radius: 12px; border: 1px solid {{ $borderColor }};">
                    <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 8px; margin-bottom: 16px;">
                        <h2 style="font-size: 16px; font-weight: 700; color: {{ $textPrimary }}; margin: 0;">
                            💡 Propuestas ({{ $preMatch->propositions->count() }})
                        </h2>
                        @php
                            $myProposition = $preMatch->propositions->where('user_id', auth()->id())->first();
                        @endphp
                        @if($preMatch->status !== 'completed' && !$myProposition)
                            <button onclick="openPropositionModal()"
                                    style="padding: 10px 16px; border: none; border-radius: 6px; background: {{ $accentColor }}; color: #003b2f; font-weight: 700; font-size: 12px; cursor: pointer; transition: all 0.2s ease;"
                                    onmouseover="this.style.backgroundColor='{{ $accentDark }}'"
                                    onmouseout="this.style.backgroundColor='{{ $accentColor }}';">
                                ➕ Nueva Propuesta
                            </button>
                        @elseif($myProposition)
                            <span style="padding: 10px 16px; background: {{ $accentLight }}; color: {{ $accentColor }}; border-radius: 6px; font-weight: 700; font-size: 12px;">
                                ✓ Ya tienes una propuesta
                            </span>
                        @elseif($preMatch->status === 'completed')
                            <span style="padding: 10px 16px; background: {{ $accentLight }}; color: {{ $accentColor }}; border-radius: 6px; font-weight: 700; font-size: 12px;">
                                ✓ Desafío Completado
                            </span>
                        @endif
                    </div>

                    <!-- Propositions List -->
                    @if($preMatch->propositions->count() > 0)
                        <div style="display: grid; gap: 12px;">
                            @foreach($preMatch->propositions as $proposition)
                            <div style="background: {{ $bgSecondary }}; padding: 16px; border-radius: 8px; border: 1px solid {{ $borderColor }};">
                                <!-- Proposition Header -->
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 12px;">
                                    <!-- Avatar + Info -->
                                    <div style="display: flex; gap: 10px; align-items: flex-start; flex: 1; min-width: 0;">
                                        <!-- Avatar Circle -->
                                        <img src="{{ $proposition->user->getAvatarUrl('small') }}"
                                             alt="{{ $proposition->user->name }}"
                                             style="width: 36px; height: 36px; min-width: 36px; border-radius: 50%; object-fit: cover; border: 2px solid {{ $accentColor }}; flex-shrink: 0;">

                                        <!-- User Info -->
                                        <div style="flex: 1; min-width: 0;">
                                            <p style="font-weight: 700; color: {{ $textPrimary }}; margin: 0 0 4px 0; word-break: break-word;">
                                                {{ $proposition->user->name }}: {{ $proposition->action }}
                                            </p>
                                            <p style="font-size: 12px; color: {{ $textSecondary }}; margin: 0;">
                                                {{ $proposition->created_at->diffForHumans() }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Status Badge -->
                                    <div style="flex-shrink: 0;">
                                        @if($proposition->validation_status === 'approved')
                                            <span style="padding: 4px 12px; background: #4CAF50; color: #fff; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-block;">
                                                ✓ Aprobado
                                            </span>
                                        @elseif($proposition->validation_status === 'rejected')
                                            <span style="padding: 4px 12px; background: {{ $redAccent }}; color: #fff; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-block;">
                                                ✕ Rechazado
                                            </span>
                                        @else
                                            <span style="padding: 4px 12px; background: #ff9500; color: #fff; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-block;">
                                                ⏳ Pendiente
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Proposition Description -->
                                <p style="color: {{ $textPrimary }}; font-size: 14px; margin: 12px 0;">
                                    {{ $proposition->description }}
                                </p>

                                <!-- Vote Progress & Info -->
                                <div style="margin-top: 12px; padding: 12px; background: {{ $bgSecondary }}; border-radius: 8px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                        <span style="font-size: 12px; color: {{ $textSecondary }};">Aprobaciones: {{ $proposition->approved_votes }}/{{ $group->users->count() }}</span>
                                        <span style="font-size: 12px; font-weight: 700; color: {{ $accentColor }};">{{ number_format($proposition->approval_percentage, 0) }}%</span>
                                    </div>
                                    <div style="width: 100%; height: 6px; background: {{ $borderColor }}; border-radius: 3px; overflow: hidden;">
                                        <div style="height: 100%; background: {{ $accentColor }}; width: {{ min($proposition->approval_percentage, 100) }}%; transition: width 0.3s ease;"></div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div style="display: flex; gap: 8px; margin-top: 12px; padding-top: 12px; border-top: 1px solid {{ $borderColor }};">
                                    @php
                                        $isMyProposition = $proposition->user_id === auth()->id();
                                        $isFulApproved = $proposition->approved_votes >= $group->users->count();
                                        $hasVoted = $proposition->votes->where('user_id', auth()->id())->isNotEmpty();
                                    @endphp

                                    @if(!$hasVoted && !$isMyProposition)
                                        <!-- Only show ACCEPT button if haven't voted and not my proposition -->
                                        <button onclick="voteProposition({{ $proposition->id }}, 'ACCEPT')"
                                                style="flex: 1; padding: 8px; border: none; border-radius: 6px; background: {{ $accentColor }}; color: #003b2f; font-weight: 700; font-size: 12px; cursor: pointer; transition: all 0.2s ease;"
                                                onmouseover="this.style.backgroundColor='{{ $accentDark }}'"
                                                onmouseout="this.style.backgroundColor='{{ $accentColor }}';">
                                            👍 Aceptar
                                        </button>
                                    @elseif($hasVoted && !$isMyProposition)
                                        <span style="flex: 1; padding: 8px; text-align: center; font-size: 12px; color: {{ $accentColor }}; font-weight: 700;">✓ Ya votaste</span>
                                    @endif

                                    @if($isMyProposition && !$isFulApproved)
                                        <button onclick="deleteProposition({{ $proposition->id }})"
                                                style="flex: 1; padding: 8px; border: none; border-radius: 6px; background: {{ $redAccent }}; color: #fff; font-weight: 700; font-size: 12px; cursor: pointer; transition: all 0.2s ease;"
                                                onmouseover="this.style.backgroundColor='#ff5252'"
                                                onmouseout="this.style.backgroundColor='{{ $redAccent }}';">
                                            🗑️ Eliminar
                                        </button>
                                    @elseif($isMyProposition && $isFulApproved)
                                        <span style="flex: 1; padding: 8px; text-align: center; font-size: 12px; color: {{ $greenAccent }}; font-weight: 700;">✓ Aprobada por todos</span>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div style="text-align: center; padding: 32px 16px; color: {{ $textSecondary }};">
                            <p style="font-size: 14px; margin: 0;">
                                📭 No hay propuestas aún
                            </p>
                            @if($preMatch->status !== 'completed')
                                <p style="font-size: 12px; margin: 8px 0 0 0;">
                                    Sé el primero en hacer una propuesta
                                </p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Sidebar -->
            <div>
                <!-- Group Info -->
                <div style="background: {{ $bgTertiary }}; padding: 20px; border-radius: 12px; border: 1px solid {{ $borderColor }}; margin-bottom: 20px;">
                    <h3 style="font-size: 14px; font-weight: 700; color: {{ $textPrimary }}; margin: 0 0 12px 0; text-transform: uppercase;">
                        👥 Grupo
                    </h3>
                    <p style="color: {{ $textSecondary }}; font-size: 13px; margin: 0;">
                        {{ $group->name }}
                    </p>
                </div>

                <!-- Stats -->
                <div style="background: {{ $bgTertiary }}; padding: 20px; border-radius: 12px; border: 1px solid {{ $borderColor }}; margin-bottom: 20px;">
                    <h3 style="font-size: 14px; font-weight: 700; color: {{ $textPrimary }}; margin: 0 0 16px 0; text-transform: uppercase;">
                        📊 Resumen
                    </h3>

                    <div style="display: grid; gap: 12px;">
                        <div style="padding: 12px; background: {{ $bgSecondary }}; border-radius: 6px;">
                            <p style="font-size: 11px; color: {{ $textSecondary }}; margin: 0; text-transform: uppercase;">
                                Propuestas
                            </p>
                            <p style="font-size: 18px; font-weight: 700; color: {{ $accentColor }}; margin: 4px 0 0 0;">
                                {{ $preMatch->propositions->count() }}
                            </p>
                        </div>

                        <div style="padding: 12px; background: {{ $bgSecondary }}; border-radius: 6px;">
                            <p style="font-size: 11px; color: {{ $textSecondary }}; margin: 0; text-transform: uppercase;">
                                Aprobadas
                            </p>
                            <p style="font-size: 18px; font-weight: 700; color: {{ $greenAccent }}; margin: 4px 0 0 0;">
                                {{ $preMatch->propositions->where('validation_status', 'approved')->count() }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Actions (Admin Only) -->
                @if($preMatch->status === 'active' && auth()->id() === $preMatch->created_by)
                    <button onclick="resolvePreMatch()"
                            style="width: 100%; padding: 12px; border: none; border-radius: 8px; background: #9C27B0; color: #fff; font-weight: 700; font-size: 13px; cursor: pointer; transition: all 0.2s ease;"
                            onmouseover="this.style.opacity='0.8'"
                            onmouseout="this.style.opacity='1';">
                        ✅ Resolver Desafío
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Responsive Grid Styles -->
    <style>
        @media (min-width: 1024px) {
            #contentGrid {
                grid-template-columns: 2fr 1fr !important;
                gap: 24px !important;
                padding: 0 !important;
            }
        }

        @media (min-width: 768px) {
            #propositionModal > div,
            #resolveModal > div {
                padding: 20px !important;
            }

            #propositionModal h2,
            #resolveModal h2 {
                font-size: 20px !important;
            }

            .checkboxes-container {
                max-height: 60vh !important;
            }
        }
    </style>

    <!-- Proposition Modal -->
    <div id="propositionModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 1rem;">
        <div style="background: {{ $bgTertiary }}; border-radius: 16px; width: 100%; max-width: 500px; padding: 16px; box-shadow: 0 20px 50px rgba(0,0,0,0.3); max-height: 90vh; overflow-y: auto;">
            <h2 style="font-size: 18px; font-weight: 700; color: {{ $textPrimary }}; margin: 0 0 16px 0;">
                💡 Nueva Propuesta
            </h2>

            <form id="propositionForm" style="display: grid; gap: 16px;">
                @csrf
                <textarea id="propositionText"
                          placeholder="Describe tu propuesta o acción para resolver el desafío..."
                          style="width: 100%; padding: 12px; border: 1px solid {{ $borderColor }}; border-radius: 8px; background: {{ $bgSecondary }}; color: {{ $textPrimary }}; font-size: 14px; font-family: inherit; resize: vertical; min-height: 100px;"
                          required></textarea>

                <div style="display: flex; gap: 12px;">
                    <button type="button" onclick="closePropositionModal()"
                            style="flex: 1; padding: 10px; border: 1px solid {{ $borderColor }}; border-radius: 6px; background: {{ $bgSecondary }}; color: {{ $textSecondary }}; font-weight: 700; cursor: pointer; transition: all 0.2s ease;"
                            onmouseover="this.style.background='{{ $borderColor }}'"
                            onmouseout="this.style.background='{{ $bgSecondary }}';">
                        Cancelar
                    </button>
                    <button type="submit"
                            style="flex: 1; padding: 10px; border: none; border-radius: 6px; background: {{ $accentColor }}; color: #003b2f; font-weight: 700; cursor: pointer; transition: all 0.2s ease;"
                            onmouseover="this.style.backgroundColor='{{ $accentDark }}'"
                            onmouseout="this.style.backgroundColor='{{ $accentColor }}';">
                        Enviar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Resolve Pre Match Modal -->
    <div id="resolveModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 1rem;">
        <div style="background: {{ $bgTertiary }}; border-radius: 16px; width: 100%; max-width: 600px; padding: 16px; box-shadow: 0 20px 50px rgba(0,0,0,0.3); max-height: 90vh; overflow-y: auto;">
            <h2 style="font-size: 18px; font-weight: 700; color: {{ $textPrimary }}; margin: 0 0 12px 0;">
                ⚖️ Resolver Desafío
            </h2> class="checkboxes-container"
            <p style="color: {{ $textSecondary }}; font-size: 13px; margin: 0 0 16px 0;">
                @if($preMatch->penalty_type === 'POINTS')
                    Selecciona cuál(es) de estas acciones sucedieron en el partido. Los perdedores tendrán una resta de {{ $preMatch->penalty_points }} puntos.
                @else
                    Selecciona cuál(es) de estas acciones sucedieron en el partido.
                @endif
            </p>

            <form id="resolveForm" style="display: grid; gap: 12px;">
                @csrf
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: {{ $textPrimary }}; margin-bottom: 8px; text-transform: uppercase;">
                        ✅ ¿Cuál de estas acciones sucedieron en el partido?
                    </label>

                    <div style="display: grid; gap: 8px; max-height: 55vh; overflow-y: auto;">
                        @foreach($preMatch->propositions as $proposition)
                            <label style="display: flex; align-items: flex-start; gap: 10px; padding: 10px; background: {{ $bgSecondary }}; border-radius: 8px; border: 1px solid {{ $borderColor }}; cursor: pointer; transition: all 0.2s ease;"
                                   onmouseover="this.style.background='{{ $isDark ? '#1a524e' : '#f0f0f0' }}'"
                                   onmouseout="this.style.background='{{ $bgSecondary }}';">
                                <input type="checkbox" name="loser_ids" value="{{ $proposition->user_id }}"
                                       style="width: 18px; height: 18px; margin-top: 2px; cursor: pointer; accent-color: {{ $accentColor }}; flex-shrink: 0;">
                                <div style="flex: 1;">
                                    <p style="font-weight: 700; color: {{ $textPrimary }}; margin: 0 0 3px 0; font-size: 13px;">
                                        {{ $proposition->action }}
                                        <span style="font-weight: 500; color: {{ $textSecondary }}; font-size: 12px;">
                                            (propuesto por {{ $proposition->user->name }})
                                        </span>
                                    </p>
                                    <p style="font-size: 12px; color: {{ $textSecondary }}; margin: 0 0 6px 0;">
                                        {{ $proposition->description }}
                                    </p>
                                    <div style="font-size: 10px;">
                                        @if($proposition->validation_status === 'approved')
                                            <span style="padding: 2px 8px; background: #4CAF50; color: #fff; border-radius: 4px; font-weight: 700;">✓ Aprobado</span>
                                        @elseif($proposition->validation_status === 'rejected')
                                            <span style="padding: 2px 8px; background: {{ $redAccent }}; color: #fff; border-radius: 4px; font-weight: 700;">✕ Rechazado</span>
                                        @else
                                            <span style="padding: 2px 8px; background: #ff9500; color: #fff; border-radius: 4px; font-weight: 700;">⏳ Pendiente</span>
                                        @endif
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>

                    @if($preMatch->propositions->count() === 0)
                        <p style="color: {{ $textSecondary }}; font-size: 12px; text-align: center; margin: 20px 0;">
                            No hay propuestas para este desafío
                        </p>
                    @endif
                </div>

                @if($preMatch->penalty_type === 'POINTS')
                    <div style="padding: 10px; background: {{ $redLight }}; border-radius: 8px; border-left: 4px solid {{ $redAccent }};">
                        <p style="font-size: 11px; color: {{ $redAccent }}; margin: 0;">
                            <strong>💔 Penalización:</strong> {{ $preMatch->penalty_points }} puntos se restarán a cada perdedor
                        </p>
                    </div>
                @else
                    <div style="padding: 10px; background: {{ $bgSecondary }}; border-radius: 8px; border-left: 4px solid #ff9500;">
                        <p style="font-size: 11px; color: {{ $textPrimary }}; margin: 0;">
                            <strong>📝 Castigo:</strong> {{ $preMatch->penalty_description }}
                        </p>
                    </div>
                @endif

                <div style="display: flex; gap: 10px;">
                    <button type="button" onclick="closeResolveModal()"
                            style="flex: 1; padding: 9px; border: 1px solid {{ $borderColor }}; border-radius: 6px; background: {{ $bgSecondary }}; color: {{ $textSecondary }}; font-weight: 700; font-size: 12px; cursor: pointer; transition: all 0.2s ease;"
                            onmouseover="this.style.background='{{ $borderColor }}'"
                            onmouseout="this.style.background='{{ $bgSecondary }}';">
                        Cancelar
                    </button>
                    <button type="submit"
                            style="flex: 1; padding: 9px; border: none; border-radius: 6px; background: #9C27B0; color: #fff; font-weight: 700; font-size: 12px; cursor: pointer; transition: all 0.2s ease;"
                            onmouseover="this.style.backgroundColor='#7B1FA2'"
                            onmouseout="this.style.backgroundColor='#9C27B0';">
                        ✅ Resolver
                    </button>
                </div>
            </form>
        </div>
    </div>


    <!-- Toast Container -->
    <div id="toast-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999; display: flex; flex-direction: column; gap: 10px; pointer-events: none;"></div>

    <script>
        // ============================================================
        // SSE (Server-Sent Events) - Real-time updates
        // ============================================================
        const preMatchId = {{ $preMatch->id }};
        let eventSource = null;

        function initializeSSE() {
            console.log('🔌 Inicializando conexión SSE para pre-match:', preMatchId);

            eventSource = new EventSource(`/api/pre-matches/${preMatchId}/events`);

            // Conexión abierta
            eventSource.addEventListener('open', function() {
                console.log('✅ Conectado a eventos en tiempo real');
                showToast('Conectado a actualizaciones en vivo', 'success', 3000);
            });

            // Recibir eventos
            eventSource.addEventListener('message', function(e) {
                try {
                    const event = JSON.parse(e.data);
                    console.log('📡 Evento recibido:', event.event, event.data);
                    handlePreMatchEvent(event);
                } catch (err) {
                    console.error('❌ Error al parsear evento:', err);
                }
            });

            // Errores
            eventSource.addEventListener('error', function() {
                console.error('❌ Error en conexión SSE');
                showToast('Desconectado de actualizaciones', 'error', 5000);
                eventSource.close();

                // Intentar reconectar después de 5 segundos
                setTimeout(() => {
                    console.log('🔄 Reintentando conexión...');
                    initializeSSE();
                }, 5000);
            });
        }

        // ============================================================
        // Manejador principal de eventos
        // ============================================================
        function handlePreMatchEvent(event) {
            const { event: eventType, data: payload } = event;

            switch(eventType) {
                case 'proposition.created':
                    handlePropositionCreated(payload);
                    break;
                case 'proposition.deleted':
                    handlePropositionDeleted(payload);
                    break;
                case 'proposition.auto_approved':
                    handlePropositionAutoApproved(payload);
                    break;
                case 'vote.created':
                    handleVoteCreated(payload);
                    break;
                case 'status.changed':
                    handleStatusChanged(payload);
                    break;
                case 'status.pending_to_active':
                    handleStatusPendingToActive(payload);
                    break;
                case 'status.resolved':
                    handleStatusResolved(payload);
                    break;
            }
        }

        // ============================================================
        // Handlers específicos por evento
        // ============================================================

        function handlePropositionCreated(payload) {
            showToast(`✅ ${payload.user_name} propuso: "${payload.action}"`, 'info', 5000);
            // Nota: La actualización del DOM se hace con un pequeño delay para que se complete el evento del servidor
            setTimeout(() => {
                location.reload();
            }, 1000);
        }

        function handlePropositionDeleted(payload) {
            showToast(`🗑️ ${payload.user_name} eliminó su propuesta`, 'warning', 4000);
            setTimeout(() => {
                location.reload();
            }, 500);
        }

        function handlePropositionAutoApproved(payload) {
            showToast(`✨ ¡"${payload.action}" fue aprobado unánimemente!`, 'success', 5000);
        }

        function handleVoteCreated(payload) {
            // Solo notificar si es el creador de la propuesta
            if (payload.proposition_creator_id === {{ auth()->id() }}) {
                showToast(`🗳️ Tu propuesta recibió un voto`, 'info', 3000);
            }
            // Actualizar barra de progreso en tiempo real
            updatePropositionProgress(payload.proposition_id, payload.approved_votes, payload.votes_count, payload.approval_percentage);
        }

        function handleStatusPendingToActive(payload) {
            showToast(`🔴 ¡El pre-match está ACTIVO! Todas las propuestas fueron aprobadas.`, 'warning', 7000);
            // Actualizar header
            updatePreMatchStatus('active');
        }

        function handleStatusChanged(payload) {
            console.log('Estado cambió de', payload.old_status, 'a', payload.new_status);
        }

        function handleStatusResolved(payload) {
            showToast(`✅ Pre-match resuelto. Penalidades aplicadas.`, 'success', 7000);
            updatePreMatchStatus('resolved');
            setTimeout(() => {
                location.reload();
            }, 2000);
        }

        // ============================================================
        // Funciones auxiliares
        // ============================================================

        function updatePropositionProgress(propositionId, approvedVotes, totalVotes, percentage) {
            // Aquí iría lógica para actualizar el UI sin reload
            // Por ahora, solo log
            console.log(`📊 Propuesta ${propositionId}: ${approvedVotes}/${totalVotes} (${percentage}%)`);
        }

        function updatePreMatchStatus(newStatus) {
            // Actualizar el header del pre-match
            const header = document.querySelector('[style*="background"]');
            if (header && newStatus === 'active') {
                header.style.background = 'linear-gradient(135deg, #ffa726, #ffb74d)';
            }
        }

        function showToast(message, type = 'info', duration = 5000) {
            const container = document.getElementById('toast-container');

            // Colores según el tipo
            const colors = {
                'success': { bg: '#4CAF50', text: '#fff' },
                'error': { bg: '#ff6b6b', text: '#fff' },
                'warning': { bg: '#ff9500', text: '#fff' },
                'info': { bg: '#2196F3', text: '#fff' }
            };

            const color = colors[type] || colors['info'];

            const toast = document.createElement('div');
            toast.style.cssText = `
                background: ${color.bg};
                color: ${color.text};
                padding: 16px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                font-size: 14px;
                font-weight: 600;
                animation: slideInRight 0.3s ease-out;
                min-width: 280px;
                max-width: 400px;
                pointer-events: auto;
            `;
            toast.textContent = message;

            container.appendChild(toast);

            // Auto-remove después de duration
            setTimeout(() => {
                toast.style.animation = 'slideOutRight 0.3s ease-out';
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }

        // Agregar keyframes para animaciones
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from {
                    transform: translateX(400px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }

            @keyframes slideOutRight {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(400px);
                    opacity: 0;
                }
            }

            @keyframes pulse {
                0%, 100% { opacity: 1; }
                50% { opacity: 0.7; }
            }

            .proposition-updating {
                animation: pulse 0.5s ease-in-out;
            }
        `;
        document.head.appendChild(style);

        // Inicializar SSE cuando se carga la página
        document.addEventListener('DOMContentLoaded', initializeSSE);

        // Cerrar conexión SSE antes de descargar la página
        window.addEventListener('beforeunload', () => {
            if (eventSource) {
                eventSource.close();
                console.log('🔌 SSE desconectado');
            }
        });

        // ============================================================
        // Functions originales (modificadas)
        // ============================================================

        function openPropositionModal() {
            document.getElementById('propositionModal').style.display = 'flex';
        }

        function closePropositionModal() {
            document.getElementById('propositionModal').style.display = 'none';
            document.getElementById('propositionText').value = '';
        }

        document.getElementById('propositionForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const text = document.getElementById('propositionText').value;

            try {
                const response = await fetch(`/api/pre-matches/${preMatchId}/propositions`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    credentials: 'include',
                    body: JSON.stringify({
                        action: text
                    })
                });

                if (!response.ok) throw new Error('Error al enviar propuesta');

                showToast('✅ Propuesta enviada exitosamente!', 'success', 3000);
                closePropositionModal();
                // El evento SSE disparará el reload automáticamente
            } catch (err) {
                showToast('❌ Error: ' + err.message, 'error', 5000);
            }
        });

        async function voteProposition(propositionId, voteType) {
            try {
                const response = await fetch(`/api/pre-match-propositions/${propositionId}/vote`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    credentials: 'include',
                    body: JSON.stringify({
                        approved: voteType === 'ACCEPT'
                    })
                });

                if (!response.ok) throw new Error('Error al votar');

                showToast('🗳️ Voto registrado', 'success', 2000);
                // El evento SSE disparará actualizaciones automáticas
            } catch (err) {
                showToast('❌ Error: ' + err.message, 'error', 5000);
            }
        }

        async function deleteProposition(propositionId) {
            if (!confirm('¿Estás seguro de que deseas eliminar esta propuesta?')) {
                return;
            }

            try {
                const response = await fetch(`/api/pre-match-propositions/${propositionId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    credentials: 'include'
                });

                if (!response.ok) throw new Error('Error al eliminar propuesta');

                showToast('✅ Propuesta eliminada exitosamente', 'success', 3000);
                // El evento SSE disparará el reload automáticamente
            } catch (err) {
                showToast('❌ Error: ' + err.message, 'error', 5000);
            }
        }

        function resolvePreMatch() {
            document.getElementById('resolveModal').style.display = 'flex';
        }

        function closeResolveModal() {
            document.getElementById('resolveModal').style.display = 'none';
        }

        // Form submission for resolution
        document.getElementById('resolveForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            // Get all checked loser_ids
            const loserCheckboxes = document.querySelectorAll('input[name="loser_ids"]:checked');
            const loser_ids = Array.from(loserCheckboxes).map(cb => parseInt(cb.value));

            const payload = {
                loser_ids: loser_ids,
                penalty_points: {{ $preMatch->penalty_points }}
            };

            try {
                const response = await fetch(`/api/pre-matches/${preMatchId}/resolve`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('input[name="_token"]').value,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                if (!response.ok) {
                    const error = await response.json();
                    showToast('Error: ' + (error.message || 'No se pudo resolver el desafío'), 'error', 5000);
                    return;
                }

                showToast('✅ Desafío resuelto exitosamente', 'success', 3000);
                closeResolveModal();
                // El evento SSE disparará el reload automáticamente

            } catch (error) {
                console.error('Error:', error);
                showToast('Error al resolver el desafío: ' + error.message, 'error', 5000);
            }
        });
    </script>

    <x-layout.bottom-navigation active-item="pre-matches" />

</x-app-layout>
