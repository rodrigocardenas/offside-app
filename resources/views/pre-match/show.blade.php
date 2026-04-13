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
                    <span data-header-status style="display: inline-block; padding: 6px 12px; background: rgba(255,255,255,0.2); border-radius: 8px; font-size: 11px; font-weight: 700;">
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
                            <div data-proposition-id="{{ $proposition->id }}" style="background: {{ $bgSecondary }}; padding: 16px; border-radius: 8px; border: 1px solid {{ $borderColor }};">
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
                                        <span data-approval-percentage style="font-size: 12px; font-weight: 700; color: {{ $accentColor }};">{{ number_format($proposition->approval_percentage, 0) }}%</span>
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
            </h2>
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

                    <div style="display: grid; gap: 8px; max-height: 55vh; overflow-y: auto;" class="checkboxes-container">
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



    <x-layout.bottom-navigation active-item="pre-matches" />

    <script>
        const preMatchId = {{ $preMatch->id }};
        let eventSource = null;
        let notificationPermission = 'default';

        function openPropositionModal() { document.getElementById('propositionModal').style.display = 'flex'; }
        function closePropositionModal() { document.getElementById('propositionModal').style.display = 'none'; document.getElementById('propositionText').value = ''; }
        function resolvePreMatch() { document.getElementById('resolveModal').style.display = 'flex'; }
        function closeResolveModal() { document.getElementById('resolveModal').style.display = 'none'; }

        async function voteProposition(id, type) {
            try {
                const r = await fetch(`/api/pre-match-propositions/${id}/vote`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                    credentials: 'include',
                    body: JSON.stringify({ approved: type === 'ACCEPT' })
                });
                if (!r.ok) throw new Error('Error al votar (HTTP ' + r.status + ')');
                const updatedProp = await r.json();
                console.log('[vote.created] Voto registrado:', updatedProp);
                showToast('✓ Voto registrado', 'success', 2000);
                // ✅ NO RECARGAMOS - SSE manejará la actualización
                console.log('[vote.created] Esperando actualización SSE...');
            } catch (e) { 
                console.error('[vote.error]', e);
                showToast('❌ ' + e.message, 'error', 5000); 
            }
        }

        async function deleteProposition(id) {
            try {
                const r = await fetch(`/api/pre-match-propositions/${id}`, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                    credentials: 'include'
                });
                if (!r.ok) throw new Error('Error al eliminar (HTTP ' + r.status + ')');
                console.log('[proposition.deleted] Propuesta eliminada');
                showToast('✓ Eliminado', 'success', 2000);
                // ✅ NO RECARGAMOS - SSE manejará la actualización
                console.log('[proposition.deleted] Esperando actualización SSE...');
            } catch (e) { 
                console.error('[proposition.delete_error]', e);
                showToast('❌ ' + e.message, 'error', 5000); 
            }
        }

        function showToast(msg, type = 'info', duration = 5000) {
            const c = { 'success': '#4CAF50', 'error': '#ff6b6b', 'warning': '#ff9500', 'info': '#2196F3' };
            const t = document.createElement('div');
            t.style.cssText = `background: ${c[type]}; color: #fff; padding: 16px; border-radius: 8px; font-weight: 600; animation: slideIn 0.3s;`;
            t.textContent = msg;
            document.getElementById('toast-container').appendChild(t);
            setTimeout(() => { t.style.animation = 'slideOut 0.3s'; setTimeout(() => t.remove(), 300); }, duration);
        }

        function requestNotificationPermission() {
            if (Notification.permission === 'granted') { notificationPermission = 'granted'; }
            else if (Notification.permission !== 'denied') {
                Notification.requestPermission().then(p => {
                    notificationPermission = p;
                    if (p === 'granted') new Notification('Offside Club', { body: 'Recibirás notificaciones', icon: '/images/logo_alone.png' });
                });
            }
        }

        function sendPushNotification(title, body) {
            try {
                const n = new Notification(title, { body, icon: '/images/logo_alone.png', tag: 'prematch-' + preMatchId });
                setTimeout(() => n.close(), 7000);
            } catch (e) { console.error(e); }
        }

        function initializePolling() {
            console.log(`📡 Iniciando polling: /api/pre-matches/${preMatchId}/events-poll`);
            let lastId = 0;
            let isConnected = false;
            
            function poll() {
                fetch(`/api/pre-matches/${preMatchId}/events-poll?last_id=${lastId}`)
                    .then(res => res.json())
                    .then(data => {
                        // Marcar como conectado en el primer poll exitoso
                        if (!isConnected) {
                            isConnected = true;
                            console.log('🎉 Conexión establecida (polling activo)');
                            showToast('✅ Conectado al servidor en tiempo real', 'success', 3000);
                        }

                        if (data.events && data.events.length > 0) {
                            console.log(`📨 Recibidos ${data.events.length} eventos`);
                            data.events.forEach(ev => {
                                console.log(`📡 Evento SSE: ${ev.event} (id: ${ev.id})`);
                                handleEvent(ev);
                            });
                            lastId = data.last_id;
                        }

                        // Siguiente polling en 1 segundo
                        setTimeout(poll, 1000);
                    })
                    .catch(err => {
                        console.error('❌ Error en polling:', err);
                        isConnected = false;
                        showToast('⚠️ Error de conexión - reintentando...', 'warning', 3000);
                        setTimeout(poll, 3000); // Reintentar en 3 segundos
                    });
            }

            poll();
        }

        function handleEvent(event) {
            const { event: type, data, is_historical } = event;
            
            // ⚠️ Asegurar que data es un objeto (decoder si viene como string)
            let eventData = data;
            if (typeof data === 'string') {
                try {
                    eventData = JSON.parse(data);
                } catch (e) {
                    console.warn('⚠️ No se pudo decodificar payload:', data);
                    eventData = data;
                }
            }
            
            const badge = is_historical ? '📜 (histórico)' : '🆕 (en vivo)';
            console.log(`📡 Evento SSE: ${type} ${badge}`);
            console.log('   Data:', eventData);

            // Ignorar pings
            if (type === 'sse.connected') {
                console.log('🎉 Conexión establecida:');
                console.log(`   Usuario: ${eventData.user_name} (${eventData.user_id})`);
                console.log(`   Pre-match: ${eventData.pre_match_id}`);
                showToast('✅ Conectado al servidor en tiempo real', 'success', 3000);
                return;
            }
            
            if (!type || type === 'ping') {
                return; // Ignorar pings
            }

            // 🔑 NO mostrar toasts para eventos históricos (solo actualizar DOM silenciosamente)
            const shouldShowToast = !is_historical;

            if (type === 'proposition.created') {
                console.log('✅ Manejando: proposition.created');
                if (shouldShowToast) showToast('✅ Nueva propuesta recibida', 'success', 3000);
                console.log('   → Recargando página para mostrar nueva propuesta');
                // Reload después de mostrar el toast
                setTimeout(() => location.reload(), 1500);
            }
            else if (type === 'proposition.deleted') {
                console.log('✅ Manejando: proposition.deleted');
                if (shouldShowToast) showToast('🗑️ Propuesta eliminada', 'warning', 3000);
                console.log('   → Recargando página para actualizar');
                setTimeout(() => location.reload(), 1500);
            }
            else if (type === 'vote.created') {
                console.log('✅ Manejando: vote.created');
                if (eventData?.proposition_id) {
                    console.log(`   → Actualizando proposición ${eventData.proposition_id} a ${eventData.approval_percentage}%`);
                    updatePropositionApprovalUI(eventData.proposition_id, eventData.approval_percentage);
                    if (shouldShowToast) showToast('📊 Voto registrado', 'info', 2000);
                } else {
                    console.warn('   ⚠️ Sin proposition_id en evento de voto');
                }
            }
            else if (type === 'proposition.auto_approved') {
                console.log('✅ Manejando: proposition.auto_approved');
                if (shouldShowToast) showToast('¡Aprobada unánimemente! 🎉', 'success', 4000);
                if (eventData?.proposition_id) {
                    console.log(`   → Recargando página para actualizar aprobación`);
                    updatePropositionStatusUI(eventData.proposition_id, 'approved');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    console.warn('   ⚠️ Sin proposition_id');
                }
            }
            else if (type === 'status.pending_to_active') {
                console.log('✅ Manejando: status.pending_to_active');
                if (shouldShowToast) showToast('🔴 Pre-match ACTIVO', 'warning', 5000);
                updateHeaderStatus('🔴 Activo');
            }
            else if (type === 'status.resolved') {
                console.log('✅ Manejando: status.resolved');
                if (shouldShowToast) showToast('✅ Desafío resuelto', 'success', 5000);
                updateHeaderStatus('✅ Completado');
                // Recargar página después de 30 segundos para mostrar cambios
                setTimeout(() => location.reload(), 30000);
            }
            else {
                console.log('⚠️ Tipo de evento no manejado:', type);
            }
        }

        // Recargar solo la sección de proposiciones
        function reloadPropositionsSection() {
            console.log('🔄 Recargando sección de proposiciones...');
            fetch(`/api/pre-matches/${preMatchId}`)
                .then(r => {
                    if (!r.ok) throw new Error('API Error: ' + r.status);
                    return r.json();
                })
                .then(data => {
                    console.log('✅ Datos recibidos del API:', data);
                    if (data && data.propositions) {
                        // Actualizar contador
                        const countEl = document.querySelector('h2');
                        if (countEl) {
                            const newCount = data.propositions.length;
                            console.log(`📊 Actualizando contador: ${newCount} proposiciones`);
                            countEl.textContent = `💡 Propuestas (${newCount})`;
                        }
                    }
                })
                .catch(err => {
                    console.error('❌ Error recargando proposiciones:', err);
                });
        }

        // Remover elemento de proposición del DOM
        function removePropositionElement(propositionId) {
            const el = document.querySelector(`[data-proposition-id="${propositionId}"]`);
            if (el) {
                el.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => el.remove(), 300);
                // Actualizar contador
                const count = document.querySelectorAll('[data-proposition-id]').length - 1;
                const countEl = document.querySelector('h2');
                if (countEl) {
                    countEl.textContent = `💡 Propuestas (${count})`;
                }
            }
        }

        // Actualizar el porcentaje de aprobación de una proposición
        function updatePropositionApprovalUI(propositionId, approvalPercentage) {
            console.log(`📊 Actualizando aprobación de proposición ${propositionId}: ${approvalPercentage}%`);
            
            // Buscar el elemento por data-proposition-id
            let el = document.querySelector(`[data-proposition-id="${propositionId}"]`);
            
            if (!el) {
                console.warn(`⚠️ Elemento de proposición ${propositionId} no encontrado en DOM`);
                console.log('📋 Elementos encontrados en DOM:', document.querySelectorAll('[data-proposition-id]').length);
                document.querySelectorAll('[data-proposition-id]').forEach((e, i) => {
                    console.log(`  ${i}: data-proposition-id="${e.getAttribute('data-proposition-id')}"`);
                });
                return;
            }
            
            console.log('✅ Elemento encontrado:', el);
            
            // Animar el elemento con pulse
            el.style.animation = 'none';
            setTimeout(() => {
                el.style.animation = 'pulse 0.5s';
            }, 10);
            
            // Buscar elementos para actualizar
            const percentEl = el.querySelector('[data-approval-percentage]');
            if (percentEl) {
                const roundedPercent = Math.round(approvalPercentage);
                percentEl.textContent = roundedPercent + '%';
                console.log(`✅ Porcentaje actualizado en DOM a: ${roundedPercent}%`);
            } else {
                console.warn('⚠️ Elemento [data-approval-percentage] no encontrado, buscando alternativas...');
                // Búsqueda alternativa: texto con % dentro del elemento
                const allText = el.textContent;
                console.log('  Texto del elemento:', allText.substring(0, 100));
            }
            
            // Actualizar barra de progreso
            const progressBars = el.querySelectorAll('div[style*="height"]');
            console.log(`  Encontrados ${progressBars.length} divs con height`);
            
            let updated = false;
            progressBars.forEach((bar, idx) => {
                const style = bar.getAttribute('style') || '';
                if (style.includes('height: 6px') || style.includes('height:6px')) {
                    const innerDiv = bar.querySelector('div');
                    if (innerDiv) {
                        const newWidth = Math.min(approvalPercentage, 100);
                        innerDiv.style.width = newWidth + '%';
                        console.log(`✅ Barra de progreso actualizada (opcion ${idx}): ${newWidth}%`);
                        updated = true;
                    }
                }
            });
            
            if (!updated) {
                console.warn('⚠️ No se encontró barra de progreso, requiere reload manual');
                // Como fallback, recarga la sección
                setTimeout(() => reloadPropositionsSection(), 1000);
            }
        }

        // Actualizar estado visual de una proposición
        function updatePropositionStatusUI(propositionId, status) {
            const el = document.querySelector(`[data-proposition-id="${propositionId}"]`);
            if (el) {
                // Agregar clase o cambiar color según estado
                el.classList.add('proposition-approved');
                // Deshabilitar botones de votación
                const voteButtons = el.querySelectorAll('button[onclick*="vote"]');
                voteButtons.forEach(btn => btn.disabled = true);
            }
        }

        // Actualizar header del status
        function updateHeaderStatus(newStatus) {
            const headerStatusEl = document.querySelector('[data-header-status]');
            if (headerStatusEl) {
                headerStatusEl.textContent = newStatus;
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const style = document.createElement('style');
            style.textContent = `@keyframes slideIn { from { transform: translateX(400px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
            @keyframes slideOut { from { transform: translateX(0); opacity: 1; } to { transform: translateX(400px); opacity: 0; } }
            @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }`;
            document.head.appendChild(style);

            requestNotificationPermission();
            initializePolling();  // Usar polling en lugar de SSE

            const pForm = document.getElementById('propositionForm');
            if (pForm) pForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const text = document.getElementById('propositionText').value;
                try {
                    const r = await fetch(`/api/pre-matches/${preMatchId}/propositions`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                        credentials: 'include',
                        body: JSON.stringify({ action: text })
                    });
                    if (!r.ok) throw new Error('Error al enviar propuesta (HTTP ' + r.status + ')');
                    const newProp = await r.json();
                    console.log('[proposition.created] Propuesta creada:', newProp);
                    showToast('✓ Propuesta enviada! Actualizando...', 'success', 2000);
                    closePropositionModal();
                    document.getElementById('propositionText').value = '';
                    
                    // ✅ El polling automático recargarà la página cuando reciba el evento
                    console.log('[proposition.created] Esperando evento de polling...');
                } catch (e) { 
                    console.error('[proposition.create_error]', e);
                    showToast('❌ ' + e.message, 'error', 5000); 
                }
            });

            const rForm = document.getElementById('resolveForm');
            if (rForm) rForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const ids = Array.from(document.querySelectorAll('input[name="loser_ids"]:checked')).map(c => parseInt(c.value));
                try {
                    const r = await fetch(`/api/pre-matches/${preMatchId}/resolve`, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('input[name="_token"]').value, 'Accept': 'application/json' },
                        body: JSON.stringify({ loser_ids: ids, penalty_points: {{ $preMatch->penalty_points ?? 0 }} })
                    });
                    if (!r.ok) throw new Error((await r.json()).message || 'Error');
                    showToast('✓ Resuelto!', 'success', 3000);
                    closeResolveModal();
                    setTimeout(() => location.reload(), 30000); // Reload después de 30 segundos
                } catch (e) { showToast('Error: ' + e.message, 'error', 5000); }
            });
        });

    </script>
</x-app-layout>
