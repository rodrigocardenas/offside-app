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
<!-- @push('scripts') -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
       console.log('Script loaded');
       
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
       
       // Initialize when document is ready
       $(document).ready(initOptions);
    </script>