<x-app-layout>
    @section('navigation-title', 'Resultados Predictivos')

    <div class="min-h-screen bg-offside-dark text-white p-1 md:p-6 pb-24">
        <!-- Header -->
        <div class="mb-8 mt-16">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-white">
                        Resultados Predictivos
                    </h1>
                    <p class="mt-2 text-offside-light">
                        Tus últimas predicciones en <span class="font-semibold text-offside-secondary">{{ $group->name }}</span>
                    </p>
                </div>
                <a href="{{ route('groups.show', $group) }}"
                   class="inline-flex items-center px-4 py-2 border border-offside-primary text-sm font-medium rounded-md text-offside-primary bg-offside-dark hover:bg-offside-primary hover:text-white transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Volver al Grupo
                </a>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-offside-primary bg-opacity-20 overflow-hidden shadow rounded-lg border border-offside-primary">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-offside-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-offside-light truncate">
                                    Total de Predicciones
                                </dt>
                                <dd class="text-lg font-medium text-white">
                                    {{ $stats['total_answers'] }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-offside-primary bg-opacity-20 overflow-hidden shadow rounded-lg border border-offside-primary">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-offside-light truncate">
                                    Predicciones Correctas
                                </dt>
                                <dd class="text-lg font-medium text-white">
                                    {{ $stats['correct_answers'] }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-offside-primary bg-opacity-20 overflow-hidden shadow rounded-lg border border-offside-primary">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-offside-light truncate">
                                    Precisión
                                </dt>
                                <dd class="text-lg font-medium text-white">
                                    {{ $stats['accuracy_percentage'] }}%
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-offside-primary bg-opacity-20 overflow-hidden shadow rounded-lg border border-offside-primary">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-offside-light truncate">
                                    Puntos Totales
                                </dt>
                                <dd class="text-lg font-medium text-white">
                                    {{ number_format($stats['total_points']) }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resultados por fecha -->
        @if($groupedAnswers->count() > 0)
            @foreach($groupedAnswers as $date => $answers)
                <div class="bg-offside-primary bg-opacity-20 shadow rounded-lg mb-6 border border-offside-primary">
                    <div class="px-6 py-4 border-b border-offside-primary">
                        <h3 class="text-lg font-medium text-white">
                            {{ \Carbon\Carbon::parse($date)->format('l, j \d\e F Y') }}
                        </h3>
                    </div>

                    <div class="divide-y divide-offside-primary">
                        @foreach($answers as $answer)
                            <div class="px-6 py-4">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <!-- Pregunta -->
                                        <div class="mb-2">
                                            <h4 class="text-sm font-medium text-white">
                                                {{ $answer->question->title }}
                                            </h4>
                                            @if($answer->question->football_match)
                                                <p class="text-xs text-offside-light mt-1">
                                                    {{ $answer->question->football_match->home_team }} vs {{ $answer->question->football_match->away_team }}
                                                </p>
                                            @endif
                                        </div>

                                        <!-- Tu respuesta -->
                                        <div class="flex items-center space-x-3">
                                            <span class="text-sm text-offside-light">Tu respuesta:</span>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $answer->is_correct ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $answer->questionOption->text }}
                                            </span>

                                            @if($answer->is_correct)
                                                <svg class="h-4 w-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            @else
                                                <svg class="h-4 w-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            @endif
                                        </div>

                                        <!-- Respuesta correcta (si la respuesta fue incorrecta) -->
                                        @if(!$answer->is_correct)
                                            <div class="mt-2">
                                                <span class="text-sm text-offside-light">Respuesta correcta:</span>
                                                @php
                                                    $correctOption = $answer->question->options->where('is_correct', true)->first();
                                                @endphp
                                                @if($correctOption)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 ml-2">
                                                        {{ $correctOption->text }}
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Puntos -->
                                    <div class="ml-4 text-right">
                                        <div class="text-sm font-medium text-white">
                                            {{ $answer->points_earned }} pts
                                        </div>
                                        <div class="text-xs text-offside-light">
                                            {{ $answer->created_at->format('H:i') }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Detalle de votos de todos los usuarios -->
                                <div class="mt-4 bg-offside-dark bg-opacity-40 rounded p-3">
                                    <h5 class="text-xs font-semibold text-offside-secondary mb-2">Votos de los miembros:</h5>
                                    <ul>
                                        @foreach(($allVotes[$answer->question_id] ?? collect()) as $vote)
                                            <li class="flex items-center mb-1">
                                                <span class="font-medium text-white mr-2">{{ $vote->user->name }}</span>
                                                <span class="text-xs px-2 py-0.5 rounded-full {{ $vote->is_correct ? 'bg-green-100 text-green-800' : 'bg-offside-primary text-white' }}">
                                                    {{ $vote->questionOption->text }}
                                                </span>
                                                @if($vote->is_correct)
                                                    <svg class="h-3 w-3 text-green-500 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @else
            <!-- Estado vacío -->
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-offside-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-white">No hay resultados aún</h3>
                <p class="mt-1 text-sm text-offside-light">
                    Aún no tienes predicciones con resultados verificados en este grupo.
                </p>
                <div class="mt-6">
                    <a href="{{ route('groups.show', $group) }}"
                       class="inline-flex items-center px-4 py-2 border border-offside-primary shadow-sm text-sm font-medium rounded-md text-offside-primary bg-offside-dark hover:bg-offside-primary hover:text-white transition-colors">
                        Ir al Grupo
                    </a>
                </div>
            </div>
        @endif
    </div>

    <!-- Menú inferior fijo -->
    <x-groups.group-bottom-menu :group="$group" />
</x-app-layout>
