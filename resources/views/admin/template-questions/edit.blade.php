@extends('layouts.app')

@php
    use Illuminate\Support\Str;
@endphp

@section('content')
<div class="min-h-screen bg-slate-950 py-12 text-white">
    <div class="mx-auto max-w-4xl space-y-6 px-6">
        <header class="flex items-center justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Administración</p>
                <h1 class="mt-1 text-3xl font-semibold text-white">Editar Plantilla de Pregunta</h1>
            </div>
            <a href="{{ route('admin.template-questions.index') }}" class="text-sm text-slate-400 hover:text-slate-200">Volver al listado</a>
        </header>

        <div class="rounded-3xl border border-slate-800 bg-slate-900/60 p-6 shadow-2xl shadow-black/40">
                    <form action="{{ route('admin.template-questions.update', $templateQuestion) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-6">
                            <label for="type" class="block text-sm font-medium text-slate-300 mb-2">
                                Tipo de Pregunta
                            </label>
                            <select name="type" id="type"
                                    class="block w-full rounded-lg border border-slate-700 bg-slate-900/60 px-3 py-2 text-white focus:border-amber-400 focus:outline-none"
                                    required
                                    {{ $templateQuestion->type === 'social' ? 'readonly' : '' }}>
                                <option value="predictive" {{ $templateQuestion->type === 'predictive' ? 'selected' : '' }}>Predictiva</option>
                                <option value="social" {{ $templateQuestion->type === 'social' ? 'selected' : '' }}>Social</option>
                            </select>
                            @error('type')
                                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                            {{-- @if($templateQuestion->type !== 'multiple_choice')
                                <p class="mt-1 text-sm text-yellow-600 dark:text-yellow-400">
                                    No se puede cambiar el tipo de pregunta una vez creada para mantener la integridad de los datos.
                                </p>
                            @endif --}}
                        </div>

                        <div class="mb-6">
                            <label for="text" class="block text-sm font-medium text-slate-300 mb-2">
                                Texto de la Pregunta
                            </label>
                            <input type="text" name="text" id="text"
                                   class="block w-full rounded-lg border border-slate-700 bg-slate-900/60 px-3 py-2 text-white focus:border-amber-400 focus:outline-none"
                                   value="{{ old('text', $templateQuestion->text) }}"
                                   required>
                            @error('text')
                                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label for="competition_id" class="block text-sm font-medium text-slate-300 mb-2">
                                Competencia
                            </label>
                            <select name="competition_id" id="competition_id"
                                    class="block w-full rounded-lg border border-slate-700 bg-slate-900/60 px-3 py-2 text-white focus:border-amber-400 focus:outline-none">
                                <option value="" {{ old('competition_id', $templateQuestion->competition_id) == '' ? 'selected' : '' }}>Sin Competencia</option>
                                @foreach($competitions as $competition)
                                    <option value="{{ $competition->id }}" {{ old('competition_id', $templateQuestion->competition_id) == $competition->id ? 'selected' : '' }}>
                                        {{ $competition->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('competition_id')
                                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-slate-300 mb-2">
                                Opciones (solo para preguntas predictivas)
                            </label>
                            <div id="options-container" class="space-y-2">
                                @if($templateQuestion->type === 'predictive')
                                    @foreach(old('options', $templateQuestion->options) as $index => $option)
                                        <div class="flex items-center gap-2 option-item">
                                            <input type="hidden" name="options[{{ $index }}][id]" value="{{ $option['id'] ?? '' }}">
                                            <input type="text"
                                                   name="options[{{ $index }}][text]"
                                                   placeholder="usa variables home_team y away_team"
                                                   value="{{ $option['text'] ?? '' }}"
                                                   class="flex-1 rounded-lg border border-slate-700 bg-slate-900/60 px-3 py-2 text-white focus:border-amber-400 focus:outline-none"
                                                   required>
                                            <input type="checkbox"
                                                   name="options[{{ $index }}][is_correct]"
                                                   class="rounded border-slate-600 bg-slate-900/60 text-amber-500 focus:ring-amber-400">
                                            <label for="options[{{ $index }}][is_correct]" class="text-sm text-slate-300">
                                                Correcta
                                            </label>
                                            <button type="button"
                                                    class="remove-option text-red-400 hover:text-red-300">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                            <button type="button"
                                    id="add-option-btn"
                                    class="mt-3 inline-flex items-center px-4 py-2 rounded-lg bg-amber-500/90 text-sm font-semibold text-white hover:bg-amber-400">
                                <i class="fas fa-plus mr-1"></i> Agregar Opción
                            </button>
                            <p class="mt-2 text-xs text-slate-400">
                                Para preguntas sociales, las opciones se generarán automáticamente con los integrantes del grupo.
                            </p>
                        </div>

                        <div class="mb-6">
                            <div class="flex items-center gap-2">
                                <input type="checkbox" name="is_featured" id="is_featured" value="1"
                                       class="rounded border-slate-600 bg-slate-900/60 text-amber-500 focus:ring-amber-400"
                                       {{ $templateQuestion->is_featured ? 'checked' : '' }}>
                                <label for="is_featured" class="block text-sm text-slate-300">
                                    ¿Pregunta destacada?
                                </label>
                            </div>
                            <p class="mt-2 text-xs text-slate-400">
                                Las preguntas destacadas se mostrarán de manera especial en la aplicación móvil.
                            </p>
                        </div>

                        <div class="mb-6">
                            <div class="flex items-center gap-2">
                                <input type="checkbox" name="used_at" id="used_at" value="1"
                                       class="rounded border-slate-600 bg-slate-900/60 text-amber-500 focus:ring-amber-400"
                                       {{ $templateQuestion->used_at ? 'checked' : '' }}>
                                <label for="used_at" class="block text-sm text-slate-300">
                                    ¿Pregunta ya utilizada?
                                </label>
                            </div>
                            <p class="mt-2 text-xs text-slate-400">
                                Marca esta casilla si la pregunta ya ha sido utilizada.
                            </p>
                        </div>

                        <div class="mt-8 flex items-center justify-end gap-3">
                            <a href="{{ route('admin.template-questions.index') }}"
                               class="rounded-lg border border-slate-600 px-4 py-2 text-sm text-slate-300 hover:border-slate-500 hover:text-slate-200">
                                Cancelar
                            </a>
                            <button type="submit"
                                    class="inline-flex items-center rounded-lg bg-amber-500/90 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-400">
                                <i class="fas fa-save mr-2"></i>
                                Actualizar Plantilla
                            </button>
                        </div>
                    </form>
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
            const $optionsSection = $optionsContainer.closest('div').closest('.mb-6');
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
                    <div class="flex items-center gap-2 option-item">
                        <input type="text"
                               name="options[${optionIndex}][text]"
                               placeholder="usa variables home_team y away_team"
                               class="flex-1 rounded-lg border border-slate-700 bg-slate-900/60 px-3 py-2 text-white focus:border-amber-400 focus:outline-none"
                               required>
                        <input type="checkbox"
                               name="options[${optionIndex}][is_correct]"
                               class="rounded border-slate-600 bg-slate-900/60 text-amber-500 focus:ring-amber-400">
                        <label for="options[${optionIndex}][is_correct]" class="text-sm text-slate-300">
                            Correcta
                        </label>
                        <button type="button"
                                class="remove-option text-red-400 hover:text-red-300">
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
@endsection
