<x-app-layout>
    <div class="min-h-screen bg-offside-dark text-white p-4 md:p-6">
        <div class="max-w-7xl mx-auto">
            <h2 class="text-3xl font-bold text-offside-light mb-8">
                {{ __('views.dashboard.title') }}
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Grupos Activos -->
                <div class="bg-white bg-opacity-10 rounded-xl p-6 backdrop-blur-sm">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-semibold text-offside-light">{{ __('views.groups.title') }}</h3>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-offside-light" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>

                    @if(auth()->user()->groups->count() > 0)
                        <ul class="space-y-3">
                            @foreach(auth()->user()->groups as $group)
                                <li>
                                    <a href="{{ route('groups.show', $group) }}"
                                       class="flex items-center justify-between p-3 bg-white/5 rounded-lg hover:bg-white/10 transition-colors">
                                        <span class="text-white">{{ $group->name }}</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-offside-light" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="text-center py-8">
                            <p class="text-gray-400 mb-4">{{ __('views.dashboard.no_groups') }}</p>
                            <a href="{{ route('groups.create') }}"
                               class="inline-block bg-gradient-to-r from-orange-500 to-orange-400 text-white px-6 py-2 rounded-lg font-semibold hover:from-orange-600 hover:to-orange-500 transition-all">
                                {{ __('views.dashboard.create_group') }}
                            </a>
                        </div>
                    @endif
                </div>

                <!-- Preguntas Disponibles -->
                <div class="bg-white bg-opacity-10 rounded-xl p-6 backdrop-blur-sm">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-semibold text-offside-light">{{ __('views.dashboard.available_questions') }}</h3>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-offside-light" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>

                    @if($questions->count() > 0)
                        <ul class="space-y-3">
                            @foreach($questions as $question)
                                <li>
                                    <a href="{{ route('questions.show', $question) }}"
                                       class="flex items-center justify-between p-3 bg-white/5 rounded-lg hover:bg-white/10 transition-colors">
                                        <div>
                                            <span class="text-white block">{{ $question->title }}</span>
                                            <span class="text-sm text-gray-400">
                                                {{ __('views.dashboard.available_until') }} {{ @userTime($question->available_until) }}
                                            </span>
                                        </div>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-offside-light" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="text-center py-8">
                            <p class="text-gray-400">{{ __('views.dashboard.no_questions') }}</p>
                        </div>
                    @endif
                </div>

                <!-- Ranking Diario -->
                <div class="bg-white bg-opacity-10 rounded-xl p-6 backdrop-blur-sm">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-semibold text-offside-light">{{ __('views.rankings.daily') }}</h3>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-offside-light" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>

                    @if($rankings->count() > 0)
                        <ul class="space-y-3">
                            @foreach($rankings->take(5) as $index => $ranking)
                                <li class="flex items-center justify-between p-3 bg-white/5 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <span class="text-lg font-bold text-offside-light w-6">{{ $index + 1 }}</span>
                                        <span class="text-white">{{ $ranking->name }}</span>
                                    </div>
                                    <span class="font-semibold text-offside-light">{{ $ranking->total_points }} {{ __('views.dashboard.pts') }}</span>
                                </li>
                            @endforeach
                        </ul>
                        <div class="mt-6 text-center">
                            <a href="{{ route('rankings.daily') }}"
                               class="inline-block bg-gradient-to-r from-orange-500 to-orange-400 text-white px-6 py-2 rounded-lg font-semibold hover:from-orange-600 hover:to-orange-500 transition-all">
                                {{ __('views.rankings.view_complete') }}
                            </a>
                        </div>
                    @else
                        <div class="text-center py-8">
                            <p class="text-gray-400">No hay datos de ranking disponibles.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
