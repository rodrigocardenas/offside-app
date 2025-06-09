<x-app-layout>
    <div class="min-h-screen bg-offside-dark text-white p-4 md:p-6 pb-28">
        <div class="max-w-4xl mx-auto mt-16">
            {{-- welcome wizard --}}
            <!-- Título de la sección -->
            <div class="flex items-center mb-8">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-offside-light" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <h2 class="text-xl font-semibold text-offside-light">Tus grupos</h2>
                <button id="activar-notificaciones" class="ml-4 px-3 py-1 bg-offside-primary text-white rounded hover:bg-orange-600" style="display:none;">
                    🔔 Activar notificaciones
                </button>
            </div>

            <!-- Lista de grupos -->
            <div class="space-y-4">
                <!-- Pestañas -->
                <div class="flex space-x-4 mb-6">
                    <button onclick="showTab('official')" class="tab-button active px-4 py-2 rounded-lg bg-offside-primary text-white" data-tab="official">
                        Competiciones Oficiales
                    </button>
                    <button onclick="showTab('amateur')" class="tab-button px-4 py-2 rounded-lg bg-white/10 text-gray-400 hover:text-white" data-tab="amateur">
                        Mis Competiciones
                    </button>
                </div>

                <!-- Grupos Oficiales -->
                <div id="official-tab" class="tab-content">
                    @if($officialGroups->isEmpty())
                        <div class="text-center py-8 text-gray-400">
                            <p>No tienes grupos de competiciones oficiales.</p>
                        </div>
                    @else
                        @foreach($officialGroups as $group)
                            <div class="bg-white bg-opacity-10 rounded-xl p-4 hover:bg-opacity-15 transition-all mb-4">
                                <div class="flex items-center justify-between">
                                    <a href="{{ route('groups.show', $group) }}" class="flex-1">
                                        <div class="flex items-center space-x-3">
                                            <div class="flex -space-x-2">
                                                @foreach($group->users->take(4) as $user)
                                                    <div class="w-8 h-8 rounded-full bg-offside-primary flex items-center justify-center text-xs font-bold ring-2 ring-offside-dark">
                                                        {{ substr($user->name, 0, 1) }}
                                                    </div>
                                                @endforeach
                                            </div>
                                            <div>
                                                <div class="flex items-center space-x-2">
                                                    <div class="bg-white rounded-full p-1">
                                                        <img src="{{ asset("images/competitions/".$group->competition?->crest_url) }}" alt="{{ $group->competition?->name }}" class="w-4 h-4">
                                                    </div>
                                                    <h3 class="font-semibold">{{ $group->name }}</h3>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                    <div class="flex space-x-2 ml-4">
                                        <button
                                            type="button"
                                            onclick="showInviteModal('{{ $group->name }}', '{{ route('groups.invite', $group->code) }}')"
                                            class="flex items-center space-x-1 text-offside-light hover:text-white transition-colors p-2 rounded-lg hover:bg-white/10"
                                            title="Compartir grupo">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                            </svg>
                                        </button>
                                        @if($group->users()->where('user_id', auth()->id())->exists())
                                            <form action="{{ route('groups.leave', $group) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="flex items-center space-x-1 text-yellow-500 hover:text-yellow-400 transition-colors p-2 rounded-lg hover:bg-white/10"
                                                    title="Salir del grupo"
                                                    onclick="return confirm('¿Estás seguro de que quieres salir de este grupo?')">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                <!-- Grupos Amateurs -->
                <div id="amateur-tab" class="tab-content hidden">
                    @if($amateurGroups->isEmpty())
                        <div class="text-center py-8 text-gray-400">
                            <p>No tienes grupos de competiciones amateurs.</p>
                        </div>
                    @else
                        @foreach($amateurGroups as $group)
                            <div class="bg-white bg-opacity-10 rounded-xl p-4 hover:bg-opacity-15 transition-all mb-4">
                                <div class="flex items-center justify-between">
                                    <a href="{{ route('groups.show', $group) }}" class="flex-1">
                                        <div class="flex items-center space-x-3">
                                            <div class="flex -space-x-2">
                                                @foreach($group->users->take(4) as $user)
                                                    <div class="w-8 h-8 rounded-full bg-offside-primary flex items-center justify-center text-xs font-bold ring-2 ring-offside-dark">
                                                        {{ substr($user->name, 0, 1) }}
                                                    </div>
                                                @endforeach
                                            </div>
                                            <div>
                                                <div class="flex items-center space-x-2">
                                                    {{-- div con diseño de budge que contiene el logo de la competicion --}}
                                                    <div class="bg-offside-primary rounded-full p-1">
                                                        <img src="{{ asset("images/competitions/".$group->competition?->crest_url) }}" alt="{{ $group->competition?->name }}" class="w-4 h-4">
                                                    </div>
                                                    <h3 class="font-semibold">{{ $group->name }}</h3>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                    <div class="flex space-x-2 ml-4">
                                        <button
                                            type="button"
                                            onclick="showInviteModal('{{ $group->name }}', '{{ route('groups.invite', $group->code) }}')"
                                            class="flex items-center space-x-1 text-offside-light hover:text-white transition-colors p-2 rounded-lg hover:bg-white/10"
                                            title="Compartir grupo">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                            </svg>
                                        </button>
                                        @if($group->users()->where('user_id', auth()->id())->exists())
                                            <form action="{{ route('groups.leave', $group) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="flex items-center space-x-1 text-yellow-500 hover:text-yellow-400 transition-colors p-2 rounded-lg hover:bg-white/10"
                                                    title="Salir del grupo"
                                                    onclick="return confirm('¿Estás seguro de que quieres salir de este grupo?')">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            <!-- Botón Crear Grupo -->
            <div class=" bottom-2   w-full  px-4">
                <a href="{{ route('groups.create') }}" class="block w-full bg-gradient-to-r from-orange-500 to-orange-400 text-white text-center py-4 rounded-full font-semibold hover:from-orange-600 hover:to-orange-500 transition-all">
                    + Crear grupo
                </a>
            </div>

            <!-- Unirse a grupo -->
            <div class="mt-4 text-center">
                <span class="text-gray-400">o</span>
                <button onclick="document.getElementById('joinGroupModal').classList.remove('hidden')" class="ml-2 text-offside-light hover:underline">
                    Unirse a un grupo
                </button>
            </div>

            <!-- Modal del Wizard -->
            <div id="welcomeWizard" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
                    <!-- Botón de cerrar -->
                    <div class="absolute top-4 right-4">
                        <button onclick="closeWizard()" class="text-gray-500 hover:text-gray-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Contenido del Wizard -->
                    <div class="p-6">
                        <!-- Paso 1: Bienvenida -->
                        <div class="wizard-step" data-step="1">
                            <div class="text-center">
                                <img src="{{ asset('images/logo_white_bg.png') }}" alt="Bienvenida" class="mx-auto mb-8 h-48 md:h-64 rounded-lg shadow-lg">
                                <h3 class="text-2xl font-bold text-gray-900 mb-4">¡Bienvenido a Offside Club!</h3>
                                <p class="text-gray-600 mb-8">Tu plataforma para predecir resultados de fútbol y competir con amigos.</p>
                            </div>
                        </div>

                        <!-- Paso 2: Preguntas Predictivas -->
                        <div class="wizard-step hidden" data-step="2">
                            <div class="text-center">
                                <img src="{{ asset('images/wizard_1.png') }}" alt="Preguntas Predictivas" class="mx-auto mb-8 h-48 md:h-64 rounded-lg shadow-lg">
                                <h3 class="text-2xl font-bold text-gray-900 mb-4">Preguntas Predictivas</h3>
                                <div class="space-y-4 text-left">
                                    <p class="text-gray-600">• Predice resultados de partidos y gana puntos</p>
                                    <p class="text-gray-600">• Cada predicción correcta vale <span class="font-bold text-[#FF6B35]">300 puntos</span></p>
                                    <p class="text-gray-600">• Las preguntas destacadas valen <span class="font-bold text-[#FF6B35]">600 puntos</span></p>
                                    <p class="text-gray-600">• Las preguntas se renuevan al inicio de cada jornada</p>
                                </div>
                            </div>
                        </div>

                        <!-- Paso 3: Preguntas Sociales -->
                        <div class="wizard-step hidden" data-step="3">
                            <div class="text-center">
                                <img src="{{ asset('images/preguntas_sociales_icon.png') }}" alt="Preguntas Sociales" class="mx-auto mb-8 h-48 md:h-64">
                                <h3 class="text-2xl font-bold text-gray-900 mb-4">Preguntas Sociales</h3>
                                <div class="space-y-4 text-left">
                                    <p class="text-gray-600">• Comparte tu opinión con otros usuarios</p>
                                    <p class="text-gray-600">• Cada respuesta te da <span class="font-bold text-[#FF6B35]">100 puntos</span></p>
                                    <p class="text-gray-600">• Las preguntas se renuevan todos los días</p>
                                    <p class="text-gray-600">• Interactúa con otros usuarios a través de likes y comentarios</p>
                                </div>
                            </div>
                        </div>

                        <!-- Paso 4: Ranking -->
                        <div class="wizard-step hidden" data-step="4">
                            <div class="text-center">
                                <img src="{{ asset('images/ranking.svg') }}" alt="Ranking" class="mx-auto mb-8 h-48 md:h-64">
                                <h3 class="text-2xl font-bold text-gray-900 mb-4">Sistema de Puntos</h3>
                                <div class="space-y-4 text-left">
                                    <p class="text-gray-600">• Compite por el primer lugar en el ranking</p>
                                    <p class="text-gray-600">• Acumula puntos respondiendo preguntas</p>
                                    <p class="text-gray-600">• Las predicciones correctas son la mejor manera de subir en el ranking</p>
                                    <p class="text-gray-600">• ¡Participa todos los días para mantener tu posición!</p>
                                </div>
                            </div>
                        </div>

                        <!-- Paso 5: Final -->
                        <div class="wizard-step hidden" data-step="5">
                            <div class="text-center">
                                <h3 class="text-2xl font-bold text-gray-900 mb-4">¡Comienza a Jugar!</h3>
                                <p class="text-gray-600 mb-8">Únete a un grupo existente o crea uno nuevo para empezar a competir.</p>
                                <div class="flex flex-col sm:flex-row justify-center space-y-4 sm:space-y-0 sm:space-x-4">
                                    <a href="{{ route('groups.create') }}" class="inline-flex justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-[#FF6B35] hover:bg-[#FF6B35]/90">
                                        Crear Grupo
                                    </a>
                                    <button type="button" onclick="showJoinGroupModal()" class="inline-flex justify-center px-6 py-3 border border-[#FF6B35] text-base font-medium rounded-md text-[#FF6B35] bg-white hover:bg-gray-50">
                                        Unirse a Grupo
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Navegación -->
                        <div class="mt-8 flex justify-between items-center">
                            <button type="button" onclick="prevStep()" class="wizard-nav-btn hidden px-4 py-2 text-[#FF6B35] hover:text-[#FF6B35]/90">
                                Anterior
                            </button>
                            <div class="flex-1 flex justify-center space-x-2">
                                <div class="w-2 h-2 rounded-full bg-[#FF6B35] wizard-dot" data-step="1"></div>
                                <div class="w-2 h-2 rounded-full bg-gray-300 wizard-dot" data-step="2"></div>
                                <div class="w-2 h-2 rounded-full bg-gray-300 wizard-dot" data-step="3"></div>
                                <div class="w-2 h-2 rounded-full bg-gray-300 wizard-dot" data-step="4"></div>
                                <div class="w-2 h-2 rounded-full bg-gray-300 wizard-dot" data-step="5"></div>
                            </div>
                            <button type="button" onclick="nextStep()" class="wizard-nav-btn px-4 py-2 text-[#FF6B35] hover:text-[#FF6B35]/90">
                                Siguiente
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal para unirse a grupo -->
            <div id="joinGroupModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
                <div class="bg-offside-dark rounded-lg p-6 w-full max-w-md">
                    <h3 class="text-xl font-bold mb-4">Unirse a un Grupo</h3>
                    <form action="{{ route('groups.join') }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label for="code" class="block text-sm font-medium mb-2">Código del Grupo</label>
                            <input type="text" name="code" id="code" class="w-full bg-offside-primary bg-opacity-20 border border-offside-primary rounded-md p-2 text-white" required>
                        </div>
                        <div class="flex justify-end space-x-2">
                            <button type="button" id="closeJoinModal" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">Cancelar</button>
                            <button type="submit" class="px-4 py-2 bg-offside-primary text-white rounded-md hover:bg-offside-primary/90">Unirse</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal de invitación -->
            <div id="inviteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center p-4 z-50">
                <div class="bg-offside-dark rounded-xl p-6 w-full max-w-md">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-white">Compartir grupo</h3>
                        <button onclick="hideInviteModal()" class="text-gray-400 hover:text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-2">Mensaje de invitación</label>
                            <textarea id="inviteMessage" rows="3" class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-offside-primary"></textarea>
                        </div>
                        <div class="flex space-x-2">
                            <button onclick="copyInviteText()" class="flex-1 flex items-center justify-center space-x-2 bg-offside-primary hover:bg-offside-primary-dark text-white px-4 py-2 rounded-lg transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-12a2 2 0 00-2-2h-2M8 5a2 2 0 002 2h4a2 2 0 002-2M8 5a2 2 0 012-2h4a2 2 0 012 2" />
                                </svg>
                                <span>Copiar</span>
                            </button>
                            <button onclick="shareOnWhatsApp()" class="flex-1 flex items-center justify-center space-x-2 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                                <span>WhatsApp</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Menú inferior fijo -->
        <div class="fixed bottom-0 left-0 right-0 bg-offside-dark border-t border-offside-primary mt-16">
            <div class="max-w-4xl mx-auto">
                <div class="flex justify-around items-center py-3">
                    <a href="{{ route('groups.index') }}" class="flex flex-col items-center text-offside-light hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span class="text-xs mt-1">Grupos</span>
                    </a>
                    <button disabled class="flex flex-col items-center text-gray-500 cursor-not-allowed">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-xs mt-1">Comunidades</span>
                    </button>
                    <a href="#" id="openFeedbackModal" class="flex flex-col items-center text-offside-light hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        <span class="text-xs mt-1">Tu opinión</span>
                    </a>
                    <a href="{{ route('profile.edit') }}" class="flex flex-col items-center text-offside-light hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span class="text-xs mt-1">Perfil</span>
                    </a>
                </div>
            </div>
        </div>

        <x-feedback-modal />
    </div>

    <script src="https://www.gstatic.com/firebasejs/9.0.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.0.0/firebase-messaging-compat.js"></script>
    <script>
        let currentInviteLink = '';
        let currentGroupName = '';

        function showInviteModal(groupName, inviteLink) {
            currentInviteLink = inviteLink;
            currentGroupName = groupName;
            const defaultMessage = `¡Únete a mi grupo "${groupName}" en Offside Club! Haz clic aquí para unirte automáticamente: ${inviteLink}`;
            document.getElementById('inviteMessage').value = defaultMessage;
            document.getElementById('inviteModal').classList.remove('hidden');
        }

        function hideInviteModal() {
            document.getElementById('inviteModal').classList.add('hidden');
        }

        function copyInviteText() {
            const text = document.getElementById('inviteMessage').value;
            copyToClipboard(text, '¡Mensaje copiado al portapapeles!');
        }

        function shareOnWhatsApp() {
            const text = encodeURIComponent(document.getElementById('inviteMessage').value);
            window.open(`https://wa.me/?text=${text}`, '_blank');
        }

        function copyGroupCode(code, groupName) {
            const text = `¡Únete a mi grupo "${groupName}" en Offside Club! Usa este código: ${code}`;
            copyToClipboard(text, '¡Código copiado al portapapeles!');
        }

        function copyToClipboard(text, successMessage) {
            navigator.clipboard.writeText(text).then(() => {
                showNotification(successMessage);
            }).catch(err => {
                console.error('Error al copiar al portapapeles:', err);
                showNotification('Error al copiar al portapapeles', 'error');
            });
        }

        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `fixed bottom-20 left-1/2 transform -translate-x-1/2 ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} text-white px-4 py-2 rounded-lg shadow-lg z-50`;
            notification.textContent = message;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.remove();
            }, 2000);
        }

        // Cerrar modal al hacer clic fuera
        document.getElementById('inviteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideInviteModal();
            }
        });

        // Funciones del Wizard
        let currentStep = 1;
        const totalSteps = 5;

        function showStep(step) {
            // Ocultar todos los pasos
            document.querySelectorAll('.wizard-step').forEach(s => {
                s.classList.add('hidden');
            });

            // Mostrar el paso actual
            const currentStepElement = document.querySelector(`.wizard-step[data-step="${step}"]`);
            if (currentStepElement) {
                currentStepElement.classList.remove('hidden');
            }

            // Actualizar los indicadores de progreso
            document.querySelectorAll('.wizard-dot').forEach(dot => {
                dot.classList.remove('bg-[#FF6B35]');
                dot.classList.add('bg-gray-300');
            });

            const currentDot = document.querySelector(`.wizard-dot[data-step="${step}"]`);
            if (currentDot) {
                currentDot.classList.remove('bg-gray-300');
                currentDot.classList.add('bg-[#FF6B35]');
            }

            // Actualizar botones de navegación
            const prevBtn = document.querySelector('.wizard-nav-btn:first-child');
            const nextBtn = document.querySelector('.wizard-nav-btn:last-child');

            if (prevBtn) {
                prevBtn.classList.toggle('hidden', step === 1);
            }

            if (nextBtn) {
                nextBtn.textContent = step === totalSteps ? 'Finalizar' : 'Siguiente';
            }
        }

        function prevStep() {
            if (currentStep > 1) {
                currentStep--;
                showStep(currentStep);
            }
        }

        function nextStep() {
            if (currentStep < totalSteps) {
                currentStep++;
                showStep(currentStep);
            } else {
                closeWizard();
            }
        }

        function closeWizard() {
            const wizard = document.getElementById('welcomeWizard');
            if (wizard) {
                wizard.classList.add('hidden');
                localStorage.setItem('wizardCompleted', 'true');
            }
        }

        // Inicializar el wizard
        document.addEventListener('DOMContentLoaded', function() {
            // Verificar si es la primera visita
            if (!localStorage.getItem('wizardCompleted')) {
                const wizard = document.getElementById('welcomeWizard');
                if (wizard) {
                    wizard.classList.remove('hidden');
                    showStep(1);
                }
            }

            // Cerrar wizard al hacer clic fuera
            const wizard = document.getElementById('welcomeWizard');
            if (wizard) {
                wizard.addEventListener('click', function(e) {
                    if (e.target === this) {
                        closeWizard();
                    }
                });
            }
        });

        function showJoinGroupModal() {
            document.getElementById('joinGroupModal').classList.remove('hidden');
            document.getElementById('welcomeWizard').classList.add('hidden');
        }

        // Cerrar modal de unirse a grupo
        document.getElementById('closeJoinModal').addEventListener('click', function() {
            document.getElementById('joinGroupModal').classList.add('hidden');
        });

        // Cerrar modal al hacer clic fuera
        document.getElementById('joinGroupModal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });

        // Script para las pestañas
        function showTab(tabName) {
            // Ocultar todos los contenidos de pestañas
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });

            // Desactivar todos los botones de pestañas
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active', 'bg-offside-primary', 'text-white');
                button.classList.add('bg-white/10', 'text-gray-400');
            });

            // Mostrar el contenido de la pestaña seleccionada
            document.getElementById(tabName + '-tab').classList.remove('hidden');

            // Activar el botón de la pestaña seleccionada
            const activeButton = document.querySelector(`[data-tab="${tabName}"]`);
            activeButton.classList.add('active', 'bg-offside-primary', 'text-white');
            activeButton.classList.remove('bg-white/10', 'text-gray-400');
        }

        const firebaseConfig = {
            apiKey: "AIzaSyDCTXfOTcgYozlv2E6pjV_QD0QZJ47aYN8",
            authDomain: "offside-dd226.firebaseapp.com",
            projectId: "offside-dd226",
            storageBucket: "offside-dd226.appspot.com",
            messagingSenderId: "249528682190",
            appId: "1:249528682190:web:c2be461351ccc44474f29f",
            measurementId: "G-EZ0VLLBGZN"
        };
        firebase.initializeApp(firebaseConfig);
        const messaging = firebase.messaging();
        const vapidKey = 'BFT3Sbs3FnMmNK-qRAKD-VsrPEpuX_mQHdEhLeJs_2CQ8uPGhuXZlGfNeNzm9kzwwWN0llK2FcYEP-hMq5_KN2M	'; // Reemplaza por tu clave pública VAPID

        document.addEventListener('DOMContentLoaded', function() {
            if ('Notification' in window && 'serviceWorker' in navigator && Notification.permission !== 'granted') {
                document.getElementById('activar-notificaciones').style.display = 'inline-block';
            }
        });

        document.getElementById('activar-notificaciones').addEventListener('click', function() {
            Notification.requestPermission().then(function(permission) {
                if (permission === 'granted') {
                    messaging.getToken({vapidKey: vapidKey}).then(function(currentToken) {
                        if (currentToken) {
                            fetch('/api/push-subscriptions', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                },
                                body: JSON.stringify({
                                    endpoint: '',
                                    public_key: '',
                                    auth_token: '',
                                    device_token: currentToken
                                })
                            }).then(res => res.json()).then(data => {
                                showNotification('¡Notificaciones activadas!');
                                document.getElementById('activar-notificaciones').style.display = 'none';
                            });
                        } else {
                            showNotification('No se pudo obtener el token de notificación', 'error');
                        }
                    }).catch(function(err) {
                        showNotification('Error al obtener el token: ' + err, 'error');
                    });
                } else {
                    showNotification('Permiso de notificaciones denegado', 'error');
                }
            });
        });
    </script>

    <style>
        .wizard-step {
            transition: all 0.3s ease-in-out;
        }
        .wizard-dot {
            transition: all 0.3s ease-in-out;
        }
    </style>
</x-app-layout>
