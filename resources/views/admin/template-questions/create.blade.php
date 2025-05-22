<x-app-layout>
    @push('styles')
    <style>
        .hidden { display: none; }
    </style>
    @endpush


    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Crear Nueva Plantilla de Pregunta') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form action="{{ route('admin.template-questions.store') }}" method="POST">
                        @csrf

                        @if ($errors->any())
                            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="mb-4">
                            <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Tipo de Pregunta
                            </label>
                            <select name="type" id="type"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    required>
                                <option value="predictive" {{ old('type') == 'predictive' ? 'selected' : '' }}>Predictiva</option>
                                <option value="social" {{ old('type') == 'social' ? 'selected' : '' }}>Social</option>
                            </select>
                            @error('type')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="text" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Texto de la Pregunta
                            </label>
                            <input type="text" name="text" id="text"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                   value="{{ old('text') }}"
                                   required>
                            @error('text')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="competition_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Competencia
                            </label>
                            <select name="competition_id" id="competition_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="" {{ old('competition_id') == '' ? 'selected' : '' }}>Sin Competencia</option>
                                @foreach($competitions as $competition)
                                    <option value="{{ $competition->id }}" {{ old('competition_id') == $competition->id ? 'selected' : '' }}>
                                        {{ $competition->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('competition_id')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror

                            <!-- Selector de partido (inicialmente oculto) -->
                            {{-- <div id="match_selector" class="mt-4 hidden">
                                <label for="football_match_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Partido
                                </label>
                                <select name="football_match_id" id="football_match_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="">Seleccione un partido</option>
                                </select>
                                @error('football_match_id')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div> --}}
                            <div class="mb-4" id="match_date_container" style="display: none;">
                                <label for="match_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Fecha del Partido
                                </label>
                                <input type="datetime-local" name="match_date" id="match_date"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                       value="{{ old('match_date') }}">
                            </div>

                            <!-- Selectores de equipos (inicialmente ocultos) -->
                            <div id="teams_selectors" class="mt-4 hidden">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="home_team_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Equipo Local
                                        </label>
                                        <select name="home_team_id" id="home_team_id"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                            <option value="">Seleccione un equipo</option>
                                        </select>
                                        @error('home_team_id')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="away_team_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Equipo Visitante
                                        </label>
                                        <select name="away_team_id" id="away_team_id"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                            <option value="">Seleccione un equipo</option>
                                        </select>
                                        @error('away_team_id')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Opciones (solo para preguntas predictivas)
                            </label>
                            <div id="options-container">
                                <!-- Options will be added here by JavaScript -->
                            </div>
                            <button type="button"
                                    id="add-option-btn"
                                    class="mt-2 inline-flex items-center px-3 py-1 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <i class="fas fa-plus mr-1"></i> Agregar Opción
                            </button>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Para preguntas sociales, las opciones se generarán automáticamente con los integrantes del grupo.
                            </p>
                        </div>

                        <div class="mb-4">
                            <div class="flex items-center">
                                <input type="checkbox" name="is_featured" id="is_featured" value="1"
                                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600">
                                <label for="is_featured" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                    ¿Pregunta destacada?
                                </label>
                            </div>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Las preguntas destacadas se mostrarán de manera especial en la aplicación móvil.
                            </p>
                        </div>

                        <div class="flex items-center justify-end mt-6">
                            <a href="{{ route('admin.template-questions.index') }}"
                               class="mr-4 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
                                Cancelar
                            </a>
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                Guardar Plantilla
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


</x-app-layout>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // Script para las opciones de preguntas
    console.log('Options script loaded');
    function initOptions() {
        console.log('Initializing options...');

        const $optionsContainer = $('#options-container');
        const $addButton = $('#add-option-btn');
        const $questionType = $('select[name="type"]');
        const $optionsSection = $optionsContainer.closest('.mb-4');
        let optionCount = 0;

        if ($optionsContainer.length === 0 || $addButton.length === 0 || $questionType.length === 0) {
            console.error('Required elements not found');
            return;
        }

        console.log('Elements found, setting up...');

        function toggleOptionsVisibility() {
            const isPredictive = $questionType.val() === 'predictive';
            $optionsSection.toggle(isPredictive);

            // If switching to predictive and no options exist, add one
            if (isPredictive && $optionsContainer.children().length === 0) {
                $optionsContainer.append(createOptionElement());
            }
        }

        function createOptionElement() {
            console.log('Creating new option element');
            const optionIndex = optionCount++;
            const optionHtml = `
                <div class="flex items-center mb-2 option-item">
                    <input type="text"
                           name="options[${optionIndex}][text]"
                           placeholder="usa variables home_team y away_team"
                           class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                           >
                    <button type="button"
                            class="remove-option ml-2 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;

            const $optionElement = $(optionHtml);

            // Add event listener for the remove button
            $optionElement.find('.remove-option').on('click', function() {
                console.log('Remove button clicked');
                $(this).closest('.option-item').remove();
            });

            return $optionElement;
        }

        // Add event listener for question type change
        $questionType.on('change', toggleOptionsVisibility);

        // Add new option when button is clicked
        $addButton.on('click', function(e) {
            console.log('Add button clicked');
            e.preventDefault();
            $optionsContainer.append(createOptionElement());
        });

        // Initialize visibility based on current selection
        toggleOptionsVisibility();

        console.log('Initialization complete');
    }
    $(document).ready(initOptions);
</script>

<script>
    // Script para los selectores de competencia y partidos
    console.log('Competition script loaded');
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM Content Loaded');
        const competitionSelect = document.getElementById('competition_id');
        const matchSelector = document.getElementById('match_selector');
        const matchSelect = document.getElementById('football_match_id');
        const teamsSelectors = document.getElementById('teams_selectors');
        const homeTeamSelect = document.getElementById('home_team_id');
        const awayTeamSelect = document.getElementById('away_team_id');
        const matchDateContainer = document.getElementById('match_date_container');
        const matchDateInput = document.getElementById('match_date');

        if (!competitionSelect || !matchSelector || !matchSelect || !teamsSelectors || !homeTeamSelect || !awayTeamSelect || !matchDateContainer || !matchDateInput) {
            console.error('No se encontraron todos los elementos necesarios');
            return;
        }

        competitionSelect.addEventListener('change', function() {
            console.log('Competition changed:', this.value);
            const competitionId = this.value;

            if (competitionId) {
                // Mostrar selector de partido y equipos
                matchSelector.classList.remove('hidden');
                teamsSelectors.classList.remove('hidden');
                matchDateContainer.style.display = 'block';
                matchDateInput.required = true;

                // Cargar partidos de la competencia
                fetch(`/admin/competitions/${competitionId}/matches`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Error al cargar partidos');
                        }
                        return response.json();
                    })
                    .then(matches => {
                        console.log('Matches loaded:', matches);
                        matchSelect.innerHTML = '<option value="">Seleccione un partido</option>';
                        matches.forEach(match => {
                            const option = new Option(
                                `${match.home_team_name} vs ${match.away_team_name} (${match.match_date})`,
                                match.id
                            );
                            matchSelect.add(option);
                        });
                    })
                    .catch(error => {
                        console.error('Error loading matches:', error);
                    });

                // Cargar equipos de la competencia
                fetch(`/admin/competitions/${competitionId}/teams`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Error al cargar equipos');
                        }
                        return response.json();
                    })
                    .then(teams => {
                        console.log('Teams loaded:', teams);
                        homeTeamSelect.innerHTML = '<option value="">Seleccione un equipo</option>';
                        awayTeamSelect.innerHTML = '<option value="">Seleccione un equipo</option>';
                        teams.forEach(team => {
                            const homeOption = new Option(team.name, team.id);
                            const awayOption = new Option(team.name, team.id);
                            homeTeamSelect.add(homeOption);
                            awayTeamSelect.add(awayOption.cloneNode(true));
                        });
                    })
                    .catch(error => {
                        console.error('Error loading teams:', error);
                    });
            } else {
                // Ocultar selectores y limpiar opciones
                matchSelector.classList.add('hidden');
                teamsSelectors.classList.add('hidden');
                matchDateContainer.style.display = 'none';
                matchDateInput.required = false;
                matchSelect.innerHTML = '<option value="">Seleccione un partido</option>';
                homeTeamSelect.innerHTML = '<option value="">Seleccione un equipo</option>';
                awayTeamSelect.innerHTML = '<option value="">Seleccione un equipo</option>';
            }
        });

        matchSelect.addEventListener('change', function() {
            console.log('Match changed:', this.value);
            const matchId = this.value;
            if (matchId) {
                fetch(`/admin/matches/${matchId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Error al cargar datos del partido');
                        }
                        return response.json();
                    })
                    .then(match => {
                        console.log('Match data loaded:', match);
                        homeTeamSelect.value = match.home_team_id;
                        awayTeamSelect.value = match.away_team_id;
                    })
                    .catch(error => {
                        console.error('Error loading match data:', error);
                    });
            }
        });

        // Si hay una competencia seleccionada (en caso de error de validación)
        if (competitionSelect.value) {
            console.log('Initial competition value:', competitionSelect.value);
            competitionSelect.dispatchEvent(new Event('change'));
        }
    });
</script>
