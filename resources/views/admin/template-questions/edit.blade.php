<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Editar Plantilla de Pregunta') }}
            </h2>
        </div>
    </x-slot>


    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form action="{{ route('admin.template-questions.update', $templateQuestion) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-4">
                            <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Tipo de Pregunta
                            </label>
                            <select name="type" id="type" 
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    required
                                    {{ $templateQuestion->type === 'social' ? 'disabled' : '' }}>
                                <option value="predictive" {{ $templateQuestion->type === 'predictive' ? 'selected' : '' }}>Predictiva</option>
                                <option value="social" {{ $templateQuestion->type === 'social' ? 'selected' : '' }}>Social</option>
                            </select>
                            @error('type')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            @if($templateQuestion->type !== 'multiple_choice')
                                <p class="mt-1 text-sm text-yellow-600 dark:text-yellow-400">
                                    No se puede cambiar el tipo de pregunta una vez creada para mantener la integridad de los datos.
                                </p>
                            @endif
                        </div>

                        <div class="mb-4">
                            <label for="text" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Texto de la Pregunta
                            </label>
                            <input type="text" name="text" id="text"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                   value="{{ old('text', $templateQuestion->text) }}"
                                   required>
                            @error('text')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Opciones (solo para preguntas predictivas)
                            </label>
                            <div id="options-container">
                                @if($templateQuestion->type === 'predictive')
                                    @foreach(old('options', $templateQuestion->options) as $index => $option)
                                        <div class="flex items-center mb-2 option-item">
                                            <input type="hidden" name="options[{{ $index }}][id]" value="{{ $option['id'] ?? '' }}">
                                            <input type="text" 
                                                   name="options[{{ $index }}][text]" 
                                                   placeholder="usa variables home_team y away_team"
                                                   value="{{ $option['text'] ?? '' }}"
                                                   class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                                   required>
                                            <button type="button" 
                                                    class="remove-option ml-2 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-200">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    @endforeach
                                @endif
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
                                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600"
                                       {{ $templateQuestion->is_featured ? 'checked' : '' }}>
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
                                Actualizar Plantilla
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
    <style>
        .hidden { display: none; }
    </style>
    @endpush

    @push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            console.log('Edit view script loaded');
            
            const $optionsContainer = $('#options-container');
            const $addButton = $('#add-option-btn');
            const $questionType = $('select[name="type"]');
            const $optionsSection = $optionsContainer.closest('.mb-4');
            let optionCount = {{ $templateQuestion->type === 'predictive' ? count(old('options', $templateQuestion->options)) : 0 }};
            
            // Initialize options visibility based on question type
            function toggleOptionsVisibility() {
                const isPredictive = $questionType.val() === 'predictive';
                $optionsSection.toggle(isPredictive);
                
                // If switching to predictive and no options exist, add one
                if (isPredictive && $optionsContainer.children().length === 0) {
                    $optionsContainer.append(createOptionElement());
                }
            }
            
            // Create a new option element
            function createOptionElement() {
                const optionIndex = optionCount++;
                const optionHtml = `
                    <div class="flex items-center mb-2 option-item">
                        <input type="text" 
                               name="options[${optionIndex}][text]" 
                               placeholder="usa variables home_team y away_team"
                               class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                               required>
                        <button type="button" 
                                class="remove-option ml-2 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-200">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
                
                const $optionElement = $(optionHtml);
                
                // Add event listener for the remove button
                $optionElement.find('.remove-option').on('click', function() {
                    $(this).closest('.option-item').remove();
                });
                
                return $optionElement;
            }
            
            // Add new option when button is clicked
            $addButton.on('click', function(e) {
                e.preventDefault();
                $optionsContainer.append(createOptionElement());
            });
            
            // Initialize visibility based on current selection
            toggleOptionsVisibility();
            
            // Add event listener for question type change (in case it's not disabled)
            $questionType.on('change', toggleOptionsVisibility);
            
            // Add event listeners for existing remove buttons
            $('.remove-option').on('click', function() {
                $(this).closest('.option-item').remove();
            });
        });
    </script>
    @endpush
</x-app-layout>
