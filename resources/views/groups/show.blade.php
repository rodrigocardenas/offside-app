<x-app-layout>
    {{-- setear en el navigation el yield navigation-title: --}}
    @section('navigation-title', $group->name)
    <div class="min-h-screen bg-offside-dark text-white p-4 md:p-6 pb-24">

        <!-- Encabezado del grupo -->


        <div class="bg-offside-primary bg-opacity-99 p-1 mb-4 fixed  left-0 right-0 w-full" style="z-index: 1000; margin-top: 2.2rem;">
            <marquee behavior="scroll" direction="left" scrollamount="5">
                @foreach($group->users->sortByDesc('points')->take(3) as $index => $user)
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
                    <div class="bg-offside-dark rounded-lg p-6 mt-16">
                        <h2 class="text-sm font-bold mb-2">
                            @if($currentMatchday)
                                JORNADA {{ $currentMatchday }}
                            @else
                                {{-- PREGUNTAS DE LA JORNADA --}}
                            @endif
                        </h2>
                        {{-- <h3 class="text-lg mb-6">Responde las preguntas de los próximos partidos:</h3> --}}

                        <!-- Carrusel de preguntas -->
                        <div class="relative">
                            <!-- Contenedor del carrusel con scroll horizontal -->
                            <div class="overflow-x-auto hide-scrollbar snap-x snap-mandatory flex space-x-4 pb-4" id="predictiveQuestionsCarousel">
                                @forelse($matchQuestions as $question)
                                    <div class="snap-center flex-none w-full" id="question{{ $question->id }}">
                                        <div class="bg-offside-primary bg-opacity-20 rounded-lg p-6 {{ $question->is_disabled || $question->available_until->addHours(4) < now() ? 'opacity-50' : '' }}">
                                            <div class="mb-4">
                                                <p class="text-sm text-offside-light">
                                                    @if($question->football_match)
                                                    {{-- if question template has home_team: use this home_team name: --}}
                                                        @if($question->templateQuestion->homeTeam)
                                                            {{ $question->templateQuestion->homeTeam->name }} vs {{ $question->templateQuestion->awayTeam->name }}
                                                        @else
                                                            {{ $question->football_match->home_team }} vs {{ $question->football_match->away_team }}
                                                        @endif
                                                    @else
                                                        {{ $question->title }}
                                                    @endif
                                                </p>
                                                <h4 class="text-xl font-bold mb-2">{{ $question->title }}</h4>
                                                <p class="text-sm text-offside-light">
                                                    @if($question->is_disabled)
                                                        Pregunta deshabilitada
                                                    @elseif($question->available_until->addHours(4) > now())
                                                        Finaliza en: <span class="countdown" data-time="{{ $question->available_until->addHours(4)->timezone('Europe/Madrid')->format('Y-m-d H:i:s') }}"></span>
                                                    @else
                                                        Partido finalizado
                                                    @endif
                                                </p>
                                                @if($question->can_modify && $userAnswers->where('question_id', $question->id)->first())
                                                    <div class="mt-2 text-sm text-blue-600">
                                                        @php
                                                            $remainingTime = $userAnswers->where('question_id', $question->id)->first()->updated_at->addMinutes(5)->diffInSeconds(now());
                                                            $minutes = floor($remainingTime / 60);
                                                            $seconds = $remainingTime % 60;
                                                        @endphp
                                                        Tiempo restante para modificar: {{ $minutes }}m {{ $seconds }}s
                                                    </div>
                                                @elseif($question->is_disabled)
                                                    <div class="mt-2 text-sm text-red-600">
                                                        Esta pregunta ya no está disponible para responder
                                                    </div>
                                                @endif
                                            </div>


                                            @if((!isset($userHasAnswered) && $question->available_until->addHours(4) > now() && !$question->is_disabled) || (isset($userHasAnswered) && $userHasAnswered->updated_at->diffInMinutes(now()) <= 5))
                                                <form action="{{ route('questions.answer', $question) }}" method="POST" class="space-y-3">
                                                    @csrf
                                                    @foreach($question->options as $option)
                                                    <button type="submit"
                                                            name="question_option_id"
                                                            value="{{ $option->id }}"
                                                            class="w-full flex justify-between items-center bg-offside-primary hover:bg-offside-secondary transition-colors p-4 rounded-lg">
                                                        <span>{{ $option->text }}</span>
                                                        <div class="flex items-center space-x-2">
                                                            @foreach($question->answers->where('question_option_id', $option->id) as $answer)
                                                                @php
                                                                    $initials = '';
                                                                    $nameParts = explode(' ', $answer->user->name);
                                                                    foreach($nameParts as $part) {
                                                                        $initials .= strtoupper(substr($part, 0, 1));
                                                                    }
                                                                    $colors = ['bg-blue-500', 'bg-green-500', 'bg-yellow-500', 'bg-red-500', 'bg-purple-500', 'bg-pink-500'];
                                                                    $color = $colors[array_rand($colors)];
                                                                @endphp
                                                                <div class="w-8 h-8 rounded-full {{ $color }} text-white flex items-center justify-center text-xs font-bold border-2 border-white shadow-sm"
                                                                    title="{{ $answer->user->name }}">
                                                                    {{ $initials }}
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </button>
                                            @endforeach
                                                </form>
                                            @else
                                                <div class="space-y-3">
                                                    @foreach($question->options as $option)
                                                        <div class="p-4 rounded-lg {{
                                                            $question->available_until->addHours(4) > now() && !$question->is_disabled
                                                                ? ($userHasAnswered->id == $option->id ? 'bg-blue-600' : 'bg-offside-primary bg-opacity-20')
                                                                : ($option->is_correct ? 'bg-green-600' : (($userHasAnswered->id ?? null) == $option->id ? 'bg-red-600' : 'bg-offside-primary bg-opacity-20'))
                                                        }}">
                                                            <div class="flex justify-between items-center">
                                                                <span>{{ $option->text }}</span>
                                                                <div class="text-sm">
                                                                    @foreach($question->answers->where('question_option_id', $option->id) as $answer)
                                                                        @php
                                                                            $initials = '';
                                                                            $nameParts = explode(' ', $answer->user->name);
                                                                            foreach($nameParts as $part) {
                                                                                $initials .= strtoupper(substr($part, 0, 1));
                                                                            }
                                                                            $colors = ['bg-blue-500', 'bg-green-500', 'bg-yellow-500', 'bg-red-500', 'bg-purple-500', 'bg-pink-500'];
                                                                            $color = $colors[array_rand($colors)];
                                                                        @endphp
                                                                        <div class="w-8 h-8 rounded-full {{ $color }} text-white flex items-center justify-center text-xs font-bold border-2 border-white shadow-sm"
                                                                             title="{{ $answer->user->name }}">
                                                                            {{ $initials }}
                                                                        </div>
                                                                        <div class="text-xs text-gray-400">
                                                                            Tiempo restante: {{ $answer->updated_at->addMinutes(5)->diffForHumans() }}
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                                @endif
                                                <!-- Like/Dislike Buttons -->
                                                <div class="flex justify-end space-x-4 mt-1">
                                                    <button type="button"
                                                            class="like-btn flex items-center {{ isset($question->templateQuestion) && $question->templateQuestion->userReactions->where('reaction', 'like')->isNotEmpty() ? 'text-green-500' : 'text-gray-400' }} hover:text-green-400 transition-colors"
                                                            data-question-id="{{ $question->id }}"
                                                            data-template-question-id="{{ $question->template_question_id }}">
                                                        <i class="fas fa-thumbs-up mr-1"></i>
                                                        <span class="like-count">
                                                            @if(isset($question->templateQuestion) && isset($question->templateQuestion->reactions))
                                                                {{ $question->templateQuestion->reactions->where('reaction', 'like')->sum('count') }}
                                                            @else
                                                                0
                                                            @endif
                                                        </span>
                                                    </button>
                                                    <button type="button"
                                                            class="dislike-btn flex items-center {{ isset($question->templateQuestion) && $question->templateQuestion->userReactions->where('reaction', 'dislike')->isNotEmpty() ? 'text-red-500' : 'text-gray-400' }} hover:text-red-400 transition-colors"
                                                            data-question-id="{{ $question->id }}"
                                                            data-template-question-id="{{ $question->template_question_id }}">
                                                        <i class="fas fa-thumbs-down mr-1"></i>
                                                        <span class="dislike-count">
                                                            @if(isset($question->templateQuestion) && isset($question->templateQuestion->reactions))
                                                                {{ $question->templateQuestion->reactions->where('reaction', 'dislike')->sum('count') }}
                                                            @else
                                                                0
                                                            @endif
                                                        </span>
                                                    </button>
                                                </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center text-gray-400 py-8">
                                        No hay preguntas disponibles para los próximos partidos.
                                    </div>
                                @endforelse
                            </div>

                            <!-- Indicadores de navegación -->
                            <div class="flex justify-center mt-1 space-x-2">
                                @foreach($matchQuestions as $index => $question)
                                    <button class="w-2 h-2 rounded-full bg-offside-light question-indicator" data-index="{{ $index }}"></button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Pregunta Social -->
                    @if($group->users->count() >= 2)
                        @if($socialQuestion)
                        <div class="bg-offside-dark rounded-lg p-6 mt-1">
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="text-sm font-bold">PREGUNTA DEL DÍA</h2>
                            </div>

                            <div class="bg-offside-primary bg-opacity-20 rounded-lg p-6">
                                <div class="mb-4">
                                    <h3 class="text-xl mb-2">{{ $socialQuestion->title }}</h3>
                                    @if($socialQuestion->description)
                                        <p class="text-sm text-offside-light">Finaliza en: <span class="countdown" data-time="{{ $socialQuestion->available_until->addHours(4)->timezone('Europe/Madrid')->format('Y-m-d H:i:s') }}"></span>
                                        </p>
                                    @endif
                                </div>

                                @php
                                    $userHasAnswered = $socialQuestion->answers->where('user_id', auth()->user()->id)->first();
                                @endphp

                                @if((!$userHasAnswered && $socialQuestion->available_until->addHours(4) > now()) || ($userHasAnswered && $userHasAnswered->updated_at->diffInMinutes(now()) <= 5))
                                    @dump($userHasAnswered)
                                    <form action="{{ route('questions.answer', $socialQuestion) }}" method="POST" class="space-y-3">
                                        @csrf
                                        @foreach($socialQuestion->options as $option)
                                            <button type="submit"
                                                    name="question_option_id"
                                                    value="{{ $option->id }}"
                                                    class="w-full text-left bg-offside-primary hover:bg-offside-primary transition-colors p-4 rounded-lg">
                                                <div class="flex justify-between items-center">
                                                    <span>{{ $option->text }}</span>
                                                    <div class="flex items-center space-x-2">
                                                        @foreach($socialQuestion->answers->where('question_option_id', $option->id) as $answer)
                                                            @php
                                                                $initials = '';
                                                                $nameParts = explode(' ', $answer->user->name);
                                                                foreach($nameParts as $part) {
                                                                    $initials .= strtoupper(substr($part, 0, 1));
                                                                }
                                                                $colors = ['bg-blue-500', 'bg-green-500', 'bg-yellow-500', 'bg-red-500', 'bg-purple-500', 'bg-pink-500'];
                                                                $color = $colors[array_rand($colors)];
                                                            @endphp
                                                            <div class="w-8 h-8 rounded-full {{ $color }} text-white flex items-center justify-center text-xs font-bold border-2 border-white shadow-sm"
                                                                 title="{{ $answer->user->name }}">
                                                                {{ $initials }}
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </button>
                                        @endforeach
                                    </form>
                                @else
                                    <div class="space-y-3">
                                    @foreach($socialQuestion->options as $option)
                                        <div class="p-4 rounded-lg {{
                                            $socialQuestion->available_until->addHours(4) > now()
                                                ? (isset($userAnswers[$socialQuestion->id]) && $userAnswers[$socialQuestion->id] == $option->id ? 'bg-blue-600' : 'bg-offside-primary bg-opacity-20')
                                                : ($option->is_correct ? 'bg-green-600' : (isset($userAnswers[$socialQuestion->id]) && $userAnswers[$socialQuestion->id] == $option->id ? 'bg-red-600' : 'bg-offside-primary bg-opacity-20'))
                                        }}">
                                            <div class="flex justify-between items-center">
                                                <span>{{ $option->text }}</span>
                                                <div class="flex items-center space-x-2">
                                                    @foreach($socialQuestion->answers->where('question_option_id', $option->id) as $answer)
                                                        @php
                                                            $initials = '';
                                                            $nameParts = explode(' ', $answer->user->name);
                                                            foreach($nameParts as $part) {
                                                                $initials .= strtoupper(substr($part, 0, 1));
                                                            }
                                                            $colors = ['bg-blue-500', 'bg-green-500', 'bg-yellow-500', 'bg-red-500', 'bg-purple-500', 'bg-pink-500'];
                                                            $color = $colors[array_rand($colors)];
                                                        @endphp
                                                        <div class="w-8 h-8 rounded-full {{ $color }} text-white flex items-center justify-center text-xs font-bold border-2 border-white shadow-sm"
                                                                title="{{ $answer->user->name }}">
                                                            {{ $initials }}
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach

                                    </div>
                                @endif
                                 <!-- Like/Dislike Buttons -->
                                 <div class="flex justify-end space-x-4 mt-1">
                                    <button type="button"
                                            class="like-btn flex items-center {{ isset($socialQuestion->templateQuestion) && $socialQuestion->templateQuestion->userReactions->where('reaction', 'like')->isNotEmpty() ? 'text-green-500' : 'text-gray-400' }} hover:text-green-400 transition-colors"
                                            data-question-id="{{ $socialQuestion->id }}"
                                            data-template-question-id="{{ $socialQuestion->template_question_id }}">
                                        <i class="fas fa-thumbs-up mr-1"></i>
                                        <span class="like-count">
                                            @if(isset($socialQuestion->templateQuestion) && isset($socialQuestion->templateQuestion->reactions))
                                                {{ $socialQuestion->templateQuestion->reactions->where('reaction', 'like')->sum('count') }}
                                            @else
                                                0
                                            @endif
                                        </span>
                                    </button>
                                    <button type="button"
                                            class="dislike-btn flex items-center {{ isset($socialQuestion->templateQuestion) && $socialQuestion->templateQuestion->userReactions->where('reaction', 'dislike')->isNotEmpty() ? 'text-red-500' : 'text-gray-400' }} hover:text-red-400 transition-colors"
                                            data-question-id="{{ $socialQuestion->id }}"
                                            data-template-question-id="{{ $socialQuestion->template_question_id }}">
                                        <i class="fas fa-thumbs-down mr-1"></i>
                                        <span class="dislike-count">
                                            @if(isset($socialQuestion->templateQuestion) && isset($socialQuestion->templateQuestion->reactions))
                                                {{ $socialQuestion->templateQuestion->reactions->where('reaction', 'dislike')->sum('count') }}
                                            @else
                                                0
                                            @endif
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endif
                    @else
                        <div class="bg-offside-dark rounded-lg p-6 mt-8">
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
                <div id="chatSection" class="bg-offside-dark rounded-lg p-6">
                    {{-- <h2 class="text-xl font-bold mb-4">Chat del {{ $group->name }}</h2> --}}
                    <div class="bg-offside-primary bg-opacity-20 rounded-lg h-[300px] flex flex-col">
                        <div class="flex-1 p-4 overflow-y-auto space-y-4">
                            @foreach($group->chatMessages as $message)
                                <div class="flex items-start space-x-3">
                                    <div class="flex-1">
                                        <div class="bg-offside-primary bg-opacity-40 rounded-lg p-3">
                                            <div class="font-medium text-sm">{{ $message->user->name }}</div>
                                            <div class="text-white">{{ $message->message }}</div>
                                            <div class="text-xs text-gray-400 mt-1">
                                                {{ $message->created_at->format('d/m/Y H:i') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="p-4 border-t border-offside-primary">
                            <form action="{{ route('chat.store', $group) }}" method="POST" class="flex items-center w-full space-x-2" id="chatForm">
                                @csrf
                                <div class="flex-1">
                                    <input type="text"
                                           name="message"
                                           class="w-full bg-offside-primary bg-opacity-40 border-0 rounded-lg text-white placeholder-gray-400 focus:ring-2 focus:ring-offside-secondary px-4 py-2"
                                           placeholder="Escribe un mensaje..."
                                           required>
                                </div>
                                <button type="submit"
                                        title="Enviar mensaje"
                                        class="bg-offside-primary text-white px-4 py-2 rounded-lg hover:bg-opacity-90 transition-colors flex items-center justify-center">
                                    <span class="hidden sm:block">Enviar</span>
                                    <i class="fas fa-paper-plane sm:hidden"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Menú inferior fijo -->
        <div class="fixed bottom-0 left-0 right-0 bg-offside-dark border-t border-offside-primary">
            <div class="max-w-4xl mx-auto">
                <div class="flex justify-around items-center py-3">
                    <!-- <a href="{{ route('dashboard') }}" class="flex flex-col items-center text-offside-light hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span class="text-xs mt-1">Inicio</span>
                    </a> -->
                    <a href="{{ route('groups.index') }}" class="flex flex-col items-center text-offside-light hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span class="text-xs mt-1">Grupos</span>
                    </a>
                    <a href="{{ route('rankings.group', $group) }}" class="flex flex-col items-center text-offside-light hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                        <span class="text-xs mt-1">Ranking</span>
                    </a>
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
            <!-- Botón flotante del chat -->
        <button id="chatToggle" class="fixed bottom-24 right-8 bg-offside-primary hover:bg-offside-primary/90 text-white rounded-full p-4 shadow-lg transition-all duration-300 flex items-center justify-center z-50">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            <span id="unreadCount" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                {{ $group->chatMessages()->count() }}
            </span>
        </button>
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
        document.getElementById('chatToggle').addEventListener('click', function() {
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

        // Manejar reacciones (like/dislike)
        document.querySelectorAll('.like-btn, .dislike-btn').forEach(button => {
            button.addEventListener('click', function() {
                const questionId = this.dataset.questionId;
                const templateQuestionId = this.dataset.templateQuestionId;
                const isLike = this.classList.contains('like-btn');
                const reaction = isLike ? 'like' : 'dislike';

                fetch(`/questions/${templateQuestionId}/react`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ reaction })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Actualizar contadores
                        const likeCount = this.closest('.flex').querySelector('.like-count');
                        const dislikeCount = this.closest('.flex').querySelector('.dislike-count');
                        likeCount.textContent = data.likes;
                        dislikeCount.textContent = data.dislikes;

                        // Actualizar estilos de los botones
                        const likeBtn = this.closest('.flex').querySelector('.like-btn');
                        const dislikeBtn = this.closest('.flex').querySelector('.dislike-btn');

                        if (data.user_reaction === 'like') {
                            likeBtn.classList.remove('text-gray-400');
                            likeBtn.classList.add('text-green-500');
                            dislikeBtn.classList.remove('text-red-500');
                            dislikeBtn.classList.add('text-gray-400');
                        } else if (data.user_reaction === 'dislike') {
                            dislikeBtn.classList.remove('text-gray-400');
                            dislikeBtn.classList.add('text-red-500');
                            likeBtn.classList.remove('text-green-500');
                            likeBtn.classList.add('text-gray-400');
                        } else {
                            likeBtn.classList.remove('text-green-500');
                            likeBtn.classList.add('text-gray-400');
                            dislikeBtn.classList.remove('text-red-500');
                            dislikeBtn.classList.add('text-gray-400');
                        }
                    }
                });
            });
        });
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
                        // Update the UI with the new counts for all questions with this template
                        $('.like-btn[data-template-question-id="' + templateQuestionId + '"] .like-count').text(data.likes);
                        $('.dislike-btn[data-template-question-id="' + templateQuestionId + '"] .dislike-count').text(data.dislikes);

                        // Update button styles
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

