<x-app-layout>
    {{-- setear en el navigation el yield navigation-title: --}}
    @section('navigation-title', $group->name)
    @if ($group->id == 69)
        @section('navigation-logo', asset("images/competitions/".$group->competition?->crest_url))
    @endif

    <div class="min-h-screen bg-offside-dark text-white p-1 md:p-6 pb-24">

        <!-- Encabezado del grupo -->
        <x-groups.group-header :group="$group" />

        <div class="bg-offside-primary bg-opacity-99 p-1 mb-4 fixed  left-0 right-0 w-full" style="z-index: 1000; margin-top: 2.2rem;">
            <marquee behavior="scroll" direction="left" scrollamount="5">
                @foreach($group->users->sortByDesc('total_points')->take(3) as $index => $user)
                    <span class="font-bold text-offside-light">
                        @if($index === 0) 🥇 @elseif($index === 1) 🥈 @elseif($index === 2) 🥉 @endif
                        {{ $user->name }} ({{ $user->total_points ?? 0 }} puntos)
                    </span>
                    @if(!$loop->last)
                        <span class="mx-2">|</span>
                    @endif
                @endforeach
            </marquee>
        </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div>

                    <!-- Preguntas de Partidos -->
                    <x-groups.group-match-questions :match-questions="$matchQuestions" :user-answers="$userAnswers" :current-matchday="$currentMatchday" />

                    <!-- Pregunta Social -->
                    @if($group->users->count() >= 2)
                        @if($socialQuestion)
                            <x-groups.group-social-question :social-question="$socialQuestion" :user-answers="$userAnswers" />
                        @endif
                    @else
                        <div class="bg-offside-dark rounded-lg p-6 mt-1">
                            <div class="text-center">
                                <h2 class="text-xl font-bold mb-2">Preguntas Sociales</h2>
                                <p class="text-offside-light">Invita a más miembros al grupo para desbloquear las preguntas sociales.</p>
                                <div class="mt-4">
                                    <p class="text-sm">Código de invitación: <span class="font-mono bg-offside-primary bg-opacity-20 px-2 py-1 rounded">{{ $group->code }}</span></p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Chat del Grupo -->
                <x-groups.group-chat :group="$group" />
            </div>
        </div>

        <!-- Menú inferior fijo -->
        <x-groups.group-bottom-menu :group="$group" />

    </div>

    <!-- Modal de Feedback -->
    <div id="feedbackModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-offside-dark rounded-lg p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">Envíanos tu opinión</h3>
                <button id="closeFeedbackModal" onclick="document.getElementById('feedbackModal').classList.add('hidden')" class="text-offside-light hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="feedbackForm">
                @csrf
                <div class="mb-4">
                    <label for="type" class="block text-sm font-medium mb-2">Tipo de comentario</label>
                    <select id="type" name="type" class="w-full bg-offside-primary bg-opacity-20 border border-offside-primary rounded-md p-2 text-white">
                        <option value="suggestion">Sugerencia</option>
                        <option value="bug">Reportar un error</option>
                        <option value="compliment">Elogio</option>
                        <option value="other">Otro</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="message" class="block text-sm font-medium mb-2">Mensaje</label>
                    <textarea id="message" name="message" rows="4" class="w-full bg-offside-primary bg-opacity-20 border border-offside-primary rounded-md p-2 text-white" required></textarea>
                </div>
                <div class="mb-4 flex items-center">
                    <input type="checkbox" id="is_anonymous" name="is_anonymous" class="rounded border-offside-primary bg-offside-primary bg-opacity-20 text-offside-primary focus:ring-offside-primary">
                    <label for="is_anonymous" class="ml-2 text-sm">Enviar como anónimo</label>
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" id="cancelFeedback" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-offside-primary text-white rounded-md hover:bg-offside-primary/90">Enviar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Premio/Penitencia -->
    @if($group->created_by === auth()->id())
    <div id="rewardPenaltyModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-offside-dark rounded-lg p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">Premio o Penitencia</h3>
                <button id="closeRewardPenaltyModal" class="text-offside-light hover:text-white">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <form id="rewardPenaltyForm">
                @csrf
                <div class="mb-4">
                    <label for="reward_or_penalty" class="block text-sm font-medium mb-2">Escribe el premio para el ganador o la penitencia para el perdedor:</label>
                    <textarea id="reward_or_penalty" name="reward_or_penalty" rows="4" class="w-full bg-offside-primary bg-opacity-20 border border-offside-primary rounded-md p-2 text-white" required>{{ $group->reward_or_penalty }}</textarea>
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" id="cancelRewardPenalty" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-offside-primary text-white rounded-md hover:bg-offside-primary/90">Guardar</button>
                </div>
            </form>
            <div id="rewardPenaltySuccess" class="hidden mt-4 text-green-500 font-bold">¡Guardado correctamente!</div>
        </div>
    </div>
    @endif

    <!-- Mostrar el premio/penitencia actual si existe -->
    @if($group->reward_or_penalty)
        <div class="max-w-2xl mx-auto my-4 p-4 bg-offside-primary bg-opacity-20 rounded-lg text-center">
            <span class="font-bold text-offside-secondary">Premio/Penitencia del grupo:</span><br>
            <span>{{ $group->reward_or_penalty }}</span>
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
        const chatForm = document.getElementById('chatForm');
        const sendMessageBtn = document.getElementById('sendMessageBtn');
        const chatMessage = document.getElementById('chatMessage');
        const chatContainer = document.querySelector('.overflow-y-auto');
        let isSubmitting = false;

        // Función para mostrar mensaje de error
        function showError(message) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'bg-red-500 text-white p-3 rounded-lg mb-4';
            errorDiv.textContent = message;
            chatContainer.insertBefore(errorDiv, chatContainer.firstChild);
            setTimeout(() => errorDiv.remove(), 5000);
        }

        chatForm.addEventListener('submit', function(e) {
            e.preventDefault();

            if (isSubmitting) return;

            isSubmitting = true;
            sendMessageBtn.disabled = true;
            sendMessageBtn.classList.add('opacity-50', 'cursor-not-allowed');

            const formData = new FormData(chatForm);

            fetch(chatForm.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    chatMessage.value = '';
                    // Agregar el nuevo mensaje al contenedor
                    chatContainer.insertAdjacentHTML('beforeend', data.html);
                    // Desplazar al final
                    chatContainer.scrollTop = chatContainer.scrollHeight;
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Ha ocurrido un error al enviar el mensaje. Por favor, intenta nuevamente.');
            })
            .finally(() => {
                isSubmitting = false;
                sendMessageBtn.disabled = false;
                sendMessageBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            });
        });
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

