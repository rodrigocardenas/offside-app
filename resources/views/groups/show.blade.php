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

        <!-- Ranking Section -->
        <div style="background: {{ $bgTertiary }}; margin: 16px; border-radius: 16px; padding: 16px; border: 1px solid {{ $borderColor }}; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="display: flex; align-items: center; justify-content: flex-start; gap: 8px; margin-bottom: 16px; font-size: 16px; font-weight: 600; color: {{ $textPrimary }};">
                <i class="fas fa-trophy" style="font-size: 16px; color: {{ $accentColor }};"></i>
                Ranking
                <a href="{{ url('/groups', $group->id) }}/ranking" style="margin-left: auto; font-size: 12px; color: {{ $textSecondary }}; cursor: pointer; padding: 4px 8px; border-radius: 12px; background: {{ $bgSecondary }}; border: 1px solid {{ $borderColor }}; transition: all 0.2s ease;"
                    onmouseover="this.style.background='{{ $isDark ? '#2a4a47' : '#f0f0f0' }}'; this.style.color='{{ $textPrimary }}';"
                    onmouseout="this.style.background='{{ $bgSecondary }}'; this.style.color='{{ $textSecondary }}';">
                    Ver más
                </a>
            </div>

            <div style="display: flex; gap: 8px; overflow-x: auto; padding-bottom: 4px; scroll-behavior: smooth;" class="hide-scrollbar">
                @forelse($group->users->sortByDesc('total_points') as $index => $user)
                    @php
                        $rankColor = $index === 0 ? '#FFD700' : ($index === 1 ? '#C0C0C0' : ($index === 2 ? '#CD7F32' : '#6c757d'));
                    @endphp
                    <div style="display: flex; align-items: center; gap: 6px; padding: 10px 12px; background: {{ $bgSecondary }}; border-radius: 12px; min-width: fit-content; transition: all 0.2s ease; cursor: pointer; border-left: 4px solid {{ $rankColor }}; border: 1px solid {{ $borderColor }}; border-left: 4px solid {{ $rankColor }};"
                        onmouseover="this.style.background='{{ $isDark ? '#1a524e' : '#f0f0f0' }}'; this.style.borderColor='{{ $accentColor }}'; this.style.transform='translateY(-2px)';"
                        onmouseout="this.style.background='{{ $bgSecondary }}'; this.style.borderColor='{{ $borderColor }}'; this.style.transform='translateY(0)';">
                        <div style="text-align: left;">
                            <div style="font-weight: 600; font-size: 12px; color: {{ $textPrimary }};">
                                {{ Str::limit($user->name, 12, '') }} <small style="font-weight: bold; color: {{ $accentColor }}; font-size: 11px;">{{ number_format($user->total_points ?? 0, 0, ',', '.') }}</small>
                            </div>
                            <div style="font-weight: bold; color: {{ $accentColor }}; font-size: 11px;">

                            </div>
                        </div>
                    </div>
                @empty
                    <div style="color: {{ $textSecondary }}; font-size: 14px; text-align: center; width: 100%;">
                        No hay jugadores en el ranking
                    </div>
                @endforelse
            </div>
        </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div>

                    <!-- Preguntas de Partidos -->
                    <x-groups.group-match-questions :match-questions="$matchQuestions" :user-answers="$userAnswers" :current-matchday="$currentMatchday" :group="$group" :theme-colors="compact('bgPrimary', 'bgSecondary', 'bgTertiary', 'textPrimary', 'textSecondary', 'borderColor', 'accentColor', 'accentDark')" />

                    <!-- Pregunta Social -->
                    {{-- @if($group->users->count() >= 2)
                        @if($socialQuestion)
                            <x-groups.group-social-question :social-question="$socialQuestion" :user-answers="$userAnswers" :theme-colors="compact('bgPrimary', 'bgSecondary', 'bgTertiary', 'textPrimary', 'textSecondary', 'borderColor', 'accentColor', 'accentDark')" />
                        @endif
                    @else
                        <x-groups.group-social-invite :group="$group" :theme-colors="compact('bgPrimary', 'bgSecondary', 'bgTertiary', 'textPrimary', 'textSecondary', 'borderColor', 'accentColor', 'accentDark')" />
                    @endif --}}
                </div>

                <!-- Chat del Grupo -->
                <x-groups.group-chat :group="$group" :theme-colors="compact('bgPrimary', 'bgSecondary', 'bgTertiary', 'textPrimary', 'textSecondary', 'borderColor', 'accentColor', 'accentDark')" />
            </div>
        </div>

        <!-- Menú inferior fijo -->
        <x-groups.group-bottom-menu :group="$group" :theme-colors="compact('bgPrimary', 'bgSecondary', 'bgTertiary', 'textPrimary', 'textSecondary', 'borderColor', 'accentColor', 'accentDark')" />

    </div>

    <!-- Modal de Feedback -->
    <div id="feedbackModal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); display: none; align-items: center; justify-content: center; z-index: 9999; padding: 20px;">
        <div style="background: {{ $bgTertiary }}; border-radius: 16px; width: 100%; max-width: 480px; padding: 28px 24px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2); border: 1px solid {{ $borderColor }};">

            {{-- Header --}}
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
                <h2 style="font-size: 22px; font-weight: 700; color: {{ $textPrimary }}; margin: 0; display: flex; align-items: center; gap: 12px;">
                    <i class="fas fa-comments" style="color: {{ $accentColor }};"></i>
                    Envíanos tu opinión
                </h2>
                <button id="closeFeedbackModal" style="background: none; border: none; font-size: 24px; color: {{ $textSecondary }}; cursor: pointer; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 6px; transition: all 0.2s ease;"
                    onmouseover="this.style.background='{{ $isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.05)' }}'; this.style.color='{{ $textPrimary }}';"
                    onmouseout="this.style.background='none'; this.style.color='{{ $textSecondary }}';">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            {{-- Descripción --}}
            <p style="color: {{ $textSecondary }}; font-size: 14px; margin-bottom: 20px; line-height: 1.5;">
                Tu opinión nos ayuda a mejorar. Comparte tus sugerencias, reporta errores o envíanos un elogio.
            </p>

            {{-- Formulario --}}
            <form id="feedbackForm" style="display: flex; flex-direction: column; gap: 16px;">
                @csrf

                {{-- Tipo de comentario --}}
                <div>
                    <label for="type" style="display: block; font-size: 14px; font-weight: 600; color: {{ $textPrimary }}; margin-bottom: 8px;">Tipo de comentario</label>
                    <select id="type" name="type" style="width: 100%; background: {{ $bgSecondary }}; border: 1px solid {{ $borderColor }}; border-radius: 10px; padding: 12px 16px; color: {{ $textPrimary }}; font-size: 14px; cursor: pointer; transition: all 0.3s ease; box-sizing: border-box;"
                        onfocus="this.style.borderColor='{{ $accentColor }}'; this.style.boxShadow='0 0 0 3px {{ $isDark ? 'rgba(0, 222, 176, 0.1)' : 'rgba(0, 222, 176, 0.08)' }}';"
                        onblur="this.style.borderColor='{{ $borderColor }}'; this.style.boxShadow='none';">
                        <option value="suggestion" style="background: {{ $bgSecondary }}; color: {{ $textPrimary }};">📝 Sugerencia</option>
                        <option value="bug" style="background: {{ $bgSecondary }}; color: {{ $textPrimary }};">🐛 Reportar un error</option>
                        <option value="compliment" style="background: {{ $bgSecondary }}; color: {{ $textPrimary }};">⭐ Elogio</option>
                        <option value="other" style="background: {{ $bgSecondary }}; color: {{ $textPrimary }};">💬 Otro</option>
                    </select>
                </div>

                {{-- Mensaje --}}
                <div>
                    <label for="message" style="display: block; font-size: 14px; font-weight: 600; color: {{ $textPrimary }}; margin-bottom: 8px;">Mensaje</label>
                    <textarea id="message" name="message" rows="4" required placeholder="Cuéntanos qué piensas..."
                        style="width: 100%; background: {{ $bgSecondary }}; border: 1px solid {{ $borderColor }}; border-radius: 10px; padding: 12px 16px; color: {{ $textPrimary }}; font-size: 14px; font-family: inherit; resize: vertical; box-sizing: border-box; transition: all 0.3s ease;"
                        onfocus="this.style.borderColor='{{ $accentColor }}'; this.style.boxShadow='0 0 0 3px {{ $isDark ? 'rgba(0, 222, 176, 0.1)' : 'rgba(0, 222, 176, 0.08)' }}';"
                        onblur="this.style.borderColor='{{ $borderColor }}'; this.style.boxShadow='none';"></textarea>
                </div>

                {{-- Opción anónima --}}
                <div style="display: flex; align-items: center; gap: 10px; padding: 12px; background: {{ $bgSecondary }}; border-radius: 8px; border: 1px solid {{ $borderColor }};">
                    <input type="checkbox" id="is_anonymous" name="is_anonymous" style="width: 18px; height: 18px; cursor: pointer; accent-color: {{ $accentColor }};">
                    <label for="is_anonymous" style="font-size: 14px; color: {{ $textPrimary }}; cursor: pointer; margin: 0; flex: 1;">
                        <i class="fas fa-mask" style="margin-right: 6px; color: {{ $accentColor }};"></i> Enviar como anónimo
                    </label>
                </div>

                {{-- Botones --}}
                <div style="display: flex; gap: 12px; margin-top: 8px;">
                    <button type="button" id="cancelFeedback" style="flex: 1; padding: 12px 16px; background: {{ $bgSecondary }}; border: 1px solid {{ $borderColor }}; border-radius: 10px; color: {{ $textPrimary }}; font-weight: 600; cursor: pointer; transition: all 0.2s ease; font-size: 15px;"
                        onmouseover="this.style.background='{{ $isDark ? '#1a524e' : '#f0f0f0' }}';"
                        onmouseout="this.style.background='{{ $bgSecondary }}';">
                        Cancelar
                    </button>
                    <button type="submit" style="flex: 1; padding: 12px 16px; background: linear-gradient(135deg, {{ $accentDark }}, {{ $accentColor }}); border: none; border-radius: 10px; color: #000; font-weight: 600; cursor: pointer; transition: all 0.2s ease; font-size: 15px; display: flex; align-items: center; justify-content: center; gap: 8px;"
                        onmouseover="this.style.opacity='0.9'; this.style.transform='translateY(-1px)';"
                        onmouseout="this.style.opacity='1'; this.style.transform='translateY(0)';">
                        <i class="fas fa-paper-plane"></i> Enviar
                    </button>
                </div>
            </form>
        </div>
    </div>




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

