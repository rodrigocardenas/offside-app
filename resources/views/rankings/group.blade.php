<x-app-layout>
    @section('navigation-title', $group->name)

    @php
        // Las variables de tema ya están compartidas globalmente por el middleware
        // Solo validar que existan en caso de que se use sin middleware
        $isDark = $isDark ?? true;
        $bgPrimary = $bgPrimary ?? '#1a1a1a';
        $bgSecondary = $bgSecondary ?? '#2a2a2a';
        $bgTertiary = $bgTertiary ?? '#333333';
        $textPrimary = $textPrimary ?? '#ffffff';
        $textSecondary = $textSecondary ?? '#b0b0b0';
        $borderColor = $borderColor ?? '#333333';
        $componentsBackground = $componentsBackground ?? '#1a524e';
        $accentColor = $accentColor ?? '#00deb0';
        $accentDark = $accentDark ?? '#003b2f';
    @endphp

    <div class="min-h-screen p-4 md:p-6" style="background: {{ $bgPrimary }};">
        <div class="max-w-4xl mx-auto mt-16">
            <!-- Encabezado con imagen del grupo -->
            <div class="mb-8 text-center">
                <div class="flex items-center justify-center mb-4">
                    @if($group->logo)
                        <img src="{{ asset('storage/' . $group->logo) }}" alt="{{ $group->name }}" class="h-16 w-16 rounded-lg mr-4 shadow-md">
                    @endif
                    <div>
                        <h1 class="text-4xl font-bold" style="color: {{ $textPrimary }};">Ranking de {{ $group->name }}</h1>
                    </div>
                </div>
                <p class="text-sm font-medium mt-3" style="color: {{ $textSecondary }};">
                    <i class="fas fa-sync-alt mr-2" style="color: {{ $accentColor }};"></i>Clasificación actualizada
                </p>
            </div>

            <!-- Lista de clasificación -->
            <div class="rounded-2xl p-6 shadow-lg" style="background: {{ $componentsBackground }}; border: 1px solid {{ $borderColor }};">
                @if($rankings->isEmpty())
                    <div class="text-center py-12">
                        <i class="fas fa-chart-line text-4xl mb-4" style="color: {{ $textSecondary }};"></i>
                        <p style="color: {{ $textSecondary }};">Aún no hay puntuaciones para mostrar en este grupo.</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($rankings as $index => $user)
                            @php
                                $medalColor = '';
                                $medalBg = '';
                                $medalText = '';
                                if ($index === 0) {
                                    $medalColor = '#fbbf24';
                                    $medalBg = '#fbbf24';
                                    $medalText = '#000';
                                } elseif ($index === 1) {
                                    $medalColor = '#d1d5db';
                                    $medalBg = '#d1d5db';
                                    $medalText = '#000';
                                } elseif ($index === 2) {
                                    $medalColor = '#f97316';
                                    $medalBg = '#f97316';
                                    $medalText = '#fff';
                                } else {
                                    $medalColor = $accentColor;
                                    $medalBg = $accentDark;
                                    $medalText = '#fff';
                                }
                            @endphp
                            <div class="flex items-center rounded-xl p-4 transition-all duration-300 hover:shadow-md"
                                 style="background: {{ $bgSecondary }}; border: 1px solid {{ $borderColor }};">
                                <!-- Avatar con borde coloreado según posición -->
                                <div class="flex-shrink-0 mr-4 relative">
                                    @if($user->avatar)
                                        <img src="{{ $user->avatar_url }}"
                                             alt="{{ $user->name }}"
                                             class="w-16 h-16 rounded-full shadow-lg object-cover"
                                             style="border: 3px solid {{ $medalColor }};">
                                    @else
                                        <div class="w-16 h-16 rounded-full flex items-center justify-center text-white font-bold text-xl shadow-lg"
                                             style="background: {{ $accentColor }}; border: 3px solid {{ $medalColor }};">
                                            {{ substr($user->name, 0, 1) }}
                                        </div>
                                    @endif
                                    @if($index < 3)
                                        <div class="absolute -bottom-1 -right-1 w-6 h-6 flex items-center justify-center rounded-full text-xs font-bold shadow-md"
                                             style="background: {{ $medalBg }}; color: {{ $medalText }};">
                                            @if($index === 0)
                                                <i class="fas fa-crown text-xs"></i>
                                            @elseif($index === 1)
                                                <i class="fas fa-medal text-xs"></i>
                                            @else
                                                <i class="fas fa-award text-xs"></i>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                <!-- Nombre y información -->
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-bold text-base truncate" style="color: {{ $textPrimary }};">{{ $user->name }}</h3>
                                    <p class="text-xs mt-1" style="color: {{ $textSecondary }};">
                                        <i class="fas fa-calendar-alt mr-1" style="color: {{ $accentColor }};"></i>Miembro desde {{ $user->created_at->format('d/m/Y') }}
                                    </p>
                                </div>

                                <!-- Puntuación -->
                                <div class="text-right flex-shrink-0">
                                    <span class="text-3xl font-bold block" style="color: {{ $accentColor }};">{{ $user->total_points ?? 0 }}</span>
                                    <p class="text-xs mt-1" style="color: {{ $textSecondary }};">pts</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Botón de volver al grupo -->
            <div class="mt-8 text-center">
                <a href="{{ route('groups.show', $group) }}" class="inline-flex items-center px-6 py-2 rounded-lg font-medium transition-all duration-300 hover:shadow-lg"
                   style="background: {{ $accentColor }}; color: #000;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Volver al grupo
                </a>
            </div>
        </div>
    </div>

    <!-- Menú inferior fijo -->
    <x-layout.bottom-navigation active-item="grupo" />

    <!-- Modal de Feedback -->
    <x-feedback-modal />

</x-app-layout>
