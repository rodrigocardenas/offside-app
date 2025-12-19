<x-app-layout
    :logo-url="asset('images/logo_alone.png')"
    alt-text="Offside Club"
>
    {{-- setear en el navigation el yield navigation-title: --}}
    @section('navigation-title', $group->name)
    @if ($group->id == 69)
        @section('navigation-logo', asset("images/competitions/".$group->competition?->crest_url))
    @endif

    @php
        $themeMode = auth()->user()->theme_mode ?? 'auto';
        $isDark = $themeMode === 'dark' || ($themeMode === 'auto' && false);

        // Colores dinámicos
        $bgPrimary = $isDark ? '#0a2e2c' : '#f5f5f5';
        $bgSecondary = $isDark ? '#0f3d3a' : '#f5f5f5';
        $bgTertiary = $isDark ? '#1a524e' : '#ffffff';
        $textPrimary = $isDark ? '#ffffff' : '#333333';
        $textSecondary = $isDark ? '#b0b0b0' : '#999999';
        $borderColor = $isDark ? '#2a4a47' : '#e0e0e0';
        $accentColor = '#00deb0';
        $accentDark = '#17b796';
    @endphp

    <div class="min-h-screen p-1 md:p-6 pb-24" style="background: {{ $bgPrimary }}; color: {{ $textPrimary }}; margin-top: 3.75rem;">

        {{-- <div class="p-1 mb-4 fixed left-0 right-0 w-full" style="z-index: 50; top: 60px; background: {{ $bgSecondary }}; opacity: 0.99;">
            <marquee behavior="scroll" direction="left" scrollamount="5">
                @foreach($group->users->sortByDesc('total_points')->take(3) as $index => $user)
                    <span class="font-bold" style="color: {{ $textSecondary }};">
                        @if($index === 0) 🥇 @elseif($index === 1) 🥈 @elseif($index === 2) 🥉 @endif
                        {{ $user->name }} ({{ $user->total_points ?? 0 }} puntos)
                    </span>
                    @if(!$loop->last)
                        <span class="mx-2">|</span>
                    @endif
                @endforeach
            </marquee>
        </div> --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8" style="margin-top: 60px;">
                <div>

                    <!-- Preguntas de Partidos -->
                    <x-groups.group-match-questions :match-questions="$matchQuestions" :user-answers="$userAnswers" :current-matchday="$currentMatchday" :theme-colors="compact('bgPrimary', 'bgSecondary', 'bgTertiary', 'textPrimary', 'textSecondary', 'borderColor', 'accentColor', 'accentDark')" />

                    <!-- Pregunta Social -->
                    @if($group->users->count() >= 2)
                        @if($socialQuestion)
                            <x-groups.group-social-question :social-question="$socialQuestion" :user-answers="$userAnswers" :theme-colors="compact('bgPrimary', 'bgSecondary', 'bgTertiary', 'textPrimary', 'textSecondary', 'borderColor', 'accentColor', 'accentDark')" />
                        @endif
                    @else
                        <div class="rounded-lg p-6 mt-1" style="background: {{ $bgPrimary }}; color: {{ $textPrimary }};">
                            <div class="text-center">
                                <h2 class="text-xl font-bold mb-2">Preguntas Sociales</h2>
                                <p style="color: {{ $textSecondary }};">Invita a más miembros al grupo para desbloquear las preguntas sociales.</p>
                                <div class="mt-4">
                                    <p class="text-sm">Código de invitación: <span class="font-mono px-2 py-1 rounded" style="background: {{ $bgSecondary }}; color: {{ $textSecondary }};">{{ $group->code }}</span></p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Chat del Grupo -->
                <x-groups.group-chat :group="$group" :theme-colors="compact('bgPrimary', 'bgSecondary', 'bgTertiary', 'textPrimary', 'textSecondary', 'borderColor', 'accentColor', 'accentDark')" />
            </div>
        </div>

        <!-- Menú inferior fijo -->
        <x-groups.group-bottom-menu :group="$group" :theme-colors="compact('bgPrimary', 'bgSecondary', 'bgTertiary', 'textPrimary', 'textSecondary', 'borderColor', 'accentColor', 'accentDark')" />

    </div>

    <!-- Modal de Feedback -->
    <div id="feedbackModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="rounded-lg p-6 w-full max-w-md" style="background: {{ $bgPrimary }}; color: {{ $textPrimary }};">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">Envíanos tu opinión</h3>
                <button id="closeFeedbackModal" onclick="document.getElementById('feedbackModal').classList.add('hidden')" style="color: {{ $textSecondary }}; cursor: pointer;" onmouseover="this.style.color='{{ $textPrimary }}'" onmouseout="this.style.color='{{ $textSecondary }}'">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="feedbackForm">
                @csrf
                <div class="mb-4">
                    <label for="type" class="block text-sm font-medium mb-2">Tipo de comentario</label>
                    <select id="type" name="type" class="w-full rounded-md p-2" style="background: {{ $bgTertiary }}; border: 1px solid {{ $borderColor }}; color: {{ $textPrimary }};">
                        <option value="suggestion">Sugerencia</option>
                        <option value="bug">Reportar un error</option>
                        <option value="compliment">Elogio</option>
                        <option value="other">Otro</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="message" class="block text-sm font-medium mb-2">Mensaje</label>
                    <textarea id="message" name="message" rows="4" class="w-full rounded-md p-2" style="background: {{ $bgTertiary }}; border: 1px solid {{ $borderColor }}; color: {{ $textPrimary }};" required></textarea>
                </div>
                <div class="mb-4 flex items-center">
                    <input type="checkbox" id="is_anonymous" name="is_anonymous" class="rounded" style="border-color: {{ $borderColor }}; background: {{ $bgTertiary }}; accent-color: {{ $accentColor }};">
                    <label for="is_anonymous" class="ml-2 text-sm">Enviar como anónimo</label>
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" id="cancelFeedback" class="px-4 py-2 rounded-md" style="background: #666; color: {{ $textPrimary }};">Cancelar</button>
                    <button type="submit" class="px-4 py-2 text-white rounded-md" style="background: {{ $accentColor }}; color: #000;">Enviar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Premio/Penitencia -->
    @if($group->created_by === auth()->id())
    <div id="rewardPenaltyModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="rounded-lg p-6 w-full max-w-md" style="background: {{ $bgPrimary }}; color: {{ $textPrimary }};">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">Premio o Penitencia</h3>
                <button id="closeRewardPenaltyModal" style="color: {{ $textSecondary }}; cursor: pointer;" onmouseover="this.style.color='{{ $textPrimary }}'" onmouseout="this.style.color='{{ $textSecondary }}'">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <form id="rewardPenaltyForm">
                @csrf
                <div class="mb-4">
                    <label for="reward_or_penalty" class="block text-sm font-medium mb-2">Escribe el premio para el ganador o la penitencia para el perdedor:</label>
                    <textarea id="reward_or_penalty" name="reward_or_penalty" rows="4" class="w-full rounded-md p-2" style="background: {{ $bgTertiary }}; border: 1px solid {{ $borderColor }}; color: {{ $textPrimary }};" required>{{ $group->reward_or_penalty }}</textarea>
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" id="cancelRewardPenalty" class="px-4 py-2 rounded-md" style="background: #666; color: {{ $textPrimary }};">Cancelar</button>
                    <button type="submit" class="px-4 py-2 text-white rounded-md" style="background: {{ $accentColor }}; color: #000;">Guardar</button>
                </div>
            </form>
            <div id="rewardPenaltySuccess" class="hidden mt-4 font-bold" style="color: #00ff00;">¡Guardado correctamente!</div>
        </div>
    </div>
    @endif

    <!-- Mostrar el premio/penitencia actual si existe -->
    @if($group->reward_or_penalty)
        <div class="max-w-2xl mx-auto my-4 p-4 rounded-lg text-center" style="background: {{ $bgSecondary }}; border: 1px solid {{ $borderColor }};">
            <span class="font-bold" style="color: {{ $textSecondary }};">Premio/Penitencia del grupo:</span><br>
            <span style="color: {{ $textPrimary }};">{{ $group->reward_or_penalty }}</span>
        </div>
    @endif

</x-app-layout>
<style>
    .hide-scrollbar::-webkit-scrollbar {
        display: none;
    }
    .hide-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
    .question-indicator.active {
        background-color: theme('colors.offside-secondary');
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.querySelector('.overflow-x-auto');
        const indicators = document.querySelectorAll('.question-indicator');
        let currentIndex = 0;

        // Actualizar indicadores al hacer scroll
        container.addEventListener('scroll', () => {
            const scrollPosition = container.scrollLeft;
            const itemWidth = container.offsetWidth;
            currentIndex = Math.round(scrollPosition / itemWidth);
            updateIndicators();
        });

        // Click en los indicadores
        indicators.forEach((indicator, index) => {
            indicator.addEventListener('click', () => {
                const itemWidth = container.offsetWidth;
                container.scrollTo({
                    left: itemWidth * index,
                    behavior: 'smooth'
                });
                currentIndex = index;
                updateIndicators();
            });
        });

        function updateIndicators() {
            indicators.forEach((indicator, index) => {
                indicator.classList.toggle('active', index === currentIndex);
            });
        }

        // Inicializar indicadores
        updateIndicators();
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Función para actualizar el contador de mensajes no leídos
        function updateUnreadCount() {
            fetch(`{{ route('chat.unread-count', $group) }}`)
                .then(response => response.json())
                .then(data => {
                    const unreadCount = document.getElementById('unreadCount');
                    if (data.unread_count > 0) {
                        unreadCount.textContent = data.unread_count;
                        unreadCount.classList.remove('hidden');
                    } else {
                        unreadCount.classList.add('hidden');
                    }
                });
        }

        // Actualizar el contador cada 30 segundos
        setInterval(updateUnreadCount, 30000);

        // Marcar mensajes como leídos cuando se hace clic en el botón del chat
        const chatToggle = document.getElementById('chatToggle');
        if (chatToggle) {
            chatToggle.addEventListener('click', function() {
                fetch(`{{ route('chat.mark-as-read', $group) }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('unreadCount').classList.add('hidden');
                    }
                });
            });
        }

        // Marcar mensajes como leídos cuando se hace scroll al chat
        const chatSection = document.getElementById('chatSection');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    fetch(`{{ route('chat.mark-as-read', $group) }}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('unreadCount').classList.add('hidden');
                        }
                    });
                }
            });
        });

        if (chatSection) {
            observer.observe(chatSection);
        }

        // Actualizar el contador inicialmente
        updateUnreadCount();
    });
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        var hash = window.location.hash;
        if(hash && /^#question\d+$/.test(hash)) {
            var $target = $(hash);
            if($target.length) {
                // Sube dos niveles: el padre del padre del div con el id
                var $scrollTo = $target.parent().parent().parent();
                if($scrollTo.length) {
                    $('html, body').animate({
                        scrollTop: $scrollTo.offset().top - 40 // Ajusta el margen si lo necesitas
                    }, 600);
                } else {
                    $('html, body').animate({
                        scrollTop: $target.offset().top - 40
                    }, 600);
                }
            }
        }
    });
</script>
<script>
    $(document).ready(function() {
        // Abrir modal
        $('#openFeedbackModal').on('click', function(e) {
            console.log('Open feedback modal clicked');

            e.preventDefault();
            $('#feedbackModal').removeClass('hidden');
        });

        // Cerrar modal
        $('#closeFeedbackModal, #cancelFeedback').on('click', function() {
            $('#feedbackModal').addClass('hidden');
        });

        // Enviar formulario
        $('#feedbackForm').on('submit', function(e) {
            e.preventDefault();

            $.ajax({
                url: '{{ route("feedback.store") }}',
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    alert(response.message);
                    $('#feedbackModal').addClass('hidden');
                    $('#feedbackForm')[0].reset();
                },
                error: function(xhr) {
                    const errors = xhr.responseJSON.errors;
                    let errorMessage = 'Por favor, corrige los siguientes errores:\n';

                    for (const field in errors) {
                        errorMessage += `- ${errors[field][0]}\n`;
                    }

                    alert(errorMessage);
                }
            });
        });
        $('#chatToggle').on('click', function() {
            $('html, body').animate({
                scrollTop: $('#chatSection').offset().top - 20
            }, 500);
        });
        console.log('Document ready');

        // Handle like button click
        $(document).on('click', '.like-btn', function(e) {
            e.preventDefault();
            const templateQuestionId = $(this).data('template-question-id');
            handleReaction(templateQuestionId, 'like');
        });

        // Handle dislike button click
        $(document).on('click', '.dislike-btn', function(e) {
            e.preventDefault();
            const templateQuestionId = $(this).data('template-question-id');
            handleReaction(templateQuestionId, 'dislike');
        });

        // Function to handle reaction (like/dislike)
        function handleReaction(templateQuestionId, type) {
            const url = '/questions/' + templateQuestionId + '/react';
            const token = $('meta[name="csrf-token"]').attr('content');

            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    _token: token,
                    reaction: type
                },
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        // Update button styles for all questions with this template
                        $('.like-btn[data-template-question-id="' + templateQuestionId + '"]').removeClass('text-green-500').addClass('text-gray-400');
                        $('.dislike-btn[data-template-question-id="' + templateQuestionId + '"]').removeClass('text-red-500').addClass('text-gray-400');

                        if (data.user_reaction === 'like') {
                            $('.like-btn[data-template-question-id="' + templateQuestionId + '"]').removeClass('text-gray-400').addClass('text-green-500');
                        } else if (data.user_reaction === 'dislike') {
                            $('.dislike-btn[data-template-question-id="' + templateQuestionId + '"]').removeClass('text-gray-400').addClass('text-red-500');
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                }
            });
        }
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const countdownElements = document.querySelectorAll('.countdown');

        countdownElements.forEach(element => {
            const endTime = new Date(element.dataset.time).getTime();

            function updateCountdown() {
                const now = new Date().getTime();
                const timeLeft = endTime - now;

                if (timeLeft <= 0) {
                    element.textContent = 'Tiempo agotado';
                    return;
                }

                const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
                const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

                element.textContent = `${days > 0 ? days + 'd ' : ''}${hours}h ${minutes}m ${seconds}s`;
            }

            // Actualizar cada segundo
            updateCountdown();
            setInterval(updateCountdown, 1000);
        });

        const chatContainer = document.querySelector('.overflow-y-auto');

        if (chatContainer) {
            // Desplazar el contenedor al final
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Variable global temporal para guardar el último botón clickeado
        let lastClickedOptionButton = null;

        // Capturar clicks en los botones de opciones
        document.querySelectorAll('button[name="question_option_id"]').forEach(button => {
            button.addEventListener('click', function(e) {
                lastClickedOptionButton = this;
                // Permitir el submit normal
            });
        });

        // Capturar el envío de formularios de preguntas
        document.querySelectorAll('form[action*="questions.answer"]').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                // Si hay un botón clickeado, agregar su valor
                if (lastClickedOptionButton && lastClickedOptionButton.form === this) {
                    formData.set('question_option_id', lastClickedOptionButton.value);
                }

                fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(response => {
                    if (response.redirected) {
                        window.location.href = response.url;
                    } else {
                        return response.text();
                    }
                })
                .then(data => {
                    if (data) {
                        window.location.reload();
                    }
                })
                .catch(error => {
                    alert('Error al enviar la respuesta. Por favor, intenta nuevamente.');
                });
            });
        });
    });
</script>
<script>
    // Forzar actualización del Service Worker para solucionar problemas de caché
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.getRegistrations().then(function(registrations) {
            for(let registration of registrations) {
                registration.update();
                console.log('Service Worker actualizado');
            }
        });

        // Limpiar cache del service worker si es necesario
        if ('caches' in window) {
            caches.keys().then(function(cacheNames) {
                return Promise.all(
                    cacheNames.map(function(cacheName) {
                        if (cacheName.includes('offside-club')) {
                            console.log('Limpiando cache:', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            });
        }
    }
</script>

