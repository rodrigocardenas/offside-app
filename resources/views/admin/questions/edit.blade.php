@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-950 py-12 text-white">
    <div class="mx-auto flex max-w-3xl flex-col gap-10 px-6">
        <!-- Header -->
        <header>
            <a href="{{ route('admin.questions.index') }}" class="inline-flex items-center gap-2 text-sky-400 hover:text-sky-300 mb-4">
                <i class="fas fa-arrow-left"></i>
                Volver a Preguntas
            </a>
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.35em] text-sky-300">Editar Pregunta</p>
                <h1 class="mt-3 text-4xl font-semibold">{{ Str::limit($question->title, 50) }}</h1>
                <p class="mt-2 text-base text-slate-400">
                    Actualiza los detalles de la pregunta, opciones y disponibilidad.
                </p>
            </div>
        </header>

        <!-- Form -->
        <form action="{{ route('admin.questions.update', $question) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Title -->
            <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6">
                <label for="title" class="block text-sm font-semibold text-white mb-2">
                    Título de la Pregunta *
                </label>
                <input type="text" id="title" name="title" value="{{ old('title', $question->title) }}"
                       class="w-full rounded-lg border border-slate-700 bg-slate-900 px-4 py-3 text-white placeholder-slate-500 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500/30"
                       placeholder="¿Cuál es tu pregunta?"
                       required>
                @error('title')
                    <p class="mt-2 text-sm text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Description -->
            <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6">
                <label for="description" class="block text-sm font-semibold text-white mb-2">
                    Descripción (opcional)
                </label>
                <textarea id="description" name="description" rows="4"
                          class="w-full rounded-lg border border-slate-700 bg-slate-900 px-4 py-3 text-white placeholder-slate-500 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500/30"
                          placeholder="Detalles adicionales sobre la pregunta...">{{ old('description', $question->description) }}</textarea>
                @error('description')
                    <p class="mt-2 text-sm text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid md:grid-cols-2 gap-6">
                <!-- Type -->
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6">
                    <label for="type" class="block text-sm font-semibold text-white mb-2">
                        Tipo de Pregunta *
                    </label>
                    <select id="type" name="type" oninput="toggleOptions()"
                            class="w-full rounded-lg border border-slate-700 bg-slate-900 px-4 py-3 text-white focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500/30"
                            required>
                        <option value="">Selecciona un tipo</option>
                        <option value="multiple_choice" {{ old('type', $question->type) === 'multiple_choice' ? 'selected' : '' }}>Opción múltiple</option>
                        <option value="boolean" {{ old('type', $question->type) === 'boolean' ? 'selected' : '' }}>Verdadero/Falso</option>
                        <option value="text" {{ old('type', $question->type) === 'text' ? 'selected' : '' }}>Texto</option>
                    </select>
                    @error('type')
                        <p class="mt-2 text-sm text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Category -->
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6">
                    <label for="category" class="block text-sm font-semibold text-white mb-2">
                        Categoría *
                    </label>
                    <select id="category" name="category"
                            class="w-full rounded-lg border border-slate-700 bg-slate-900 px-4 py-3 text-white focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500/30"
                            required>
                        <option value="">Selecciona categoría</option>
                        <option value="predictive" {{ old('category', $question->category) === 'predictive' ? 'selected' : '' }}>Predictiva</option>
                        <option value="social" {{ old('category', $question->category) === 'social' ? 'selected' : '' }}>Social</option>
                    </select>
                    @error('category')
                        <p class="mt-2 text-sm text-rose-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-6">
                <!-- Points -->
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6">
                    <label for="points" class="block text-sm font-semibold text-white mb-2">
                        Puntos *
                    </label>
                    <input type="number" id="points" name="points" value="{{ old('points', $question->points) }}"
                           class="w-full rounded-lg border border-slate-700 bg-slate-900 px-4 py-3 text-white focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500/30"
                           min="1"
                           required>
                    @error('points')
                        <p class="mt-2 text-sm text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Available Until -->
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6">
                    <label for="available_until" class="block text-sm font-semibold text-white mb-2">
                        Disponible Hasta *
                    </label>
                    <input type="datetime-local" id="available_until" name="available_until"
                           value="{{ old('available_until', $question->available_until ? $question->available_until->format('Y-m-d\TH:i') : '') }}"
                           class="w-full rounded-lg border border-slate-700 bg-slate-900 px-4 py-3 text-white focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500/30"
                           required>
                    @error('available_until')
                        <p class="mt-2 text-sm text-rose-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Featured -->
            <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" id="is_featured" name="is_featured" value="1"
                           class="rounded border-slate-600 bg-slate-900 text-sky-500 focus:ring-2 focus:ring-sky-500/30"
                           {{ old('is_featured', $question->is_featured) ? 'checked' : '' }}>
                    <span class="font-semibold text-white">Destacar esta pregunta</span>
                </label>
            </div>

            <!-- Options (for multiple choice) -->
            <div id="options-section" class="hidden rounded-2xl border border-slate-800 bg-slate-900/60 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-white">Opciones</h3>
                    <button type="button" onclick="addOption()"
                            class="text-sky-400 hover:text-sky-300 text-sm font-semibold">
                        <i class="fas fa-plus mr-1"></i>Agregar opción
                    </button>
                </div>
                <div id="options-list" class="space-y-4">
                    <!-- Options will be added here -->
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex gap-3 pt-6">
                <button type="submit"
                        class="flex-1 rounded-lg bg-sky-500/90 px-6 py-3 font-semibold text-white hover:bg-sky-400 transition-colors">
                    <i class="fas fa-check mr-2"></i>Guardar Cambios
                </button>
                <a href="{{ route('admin.questions.index') }}"
                   class="flex-1 rounded-lg border border-slate-600/40 bg-slate-500/10 px-6 py-3 text-center font-semibold text-slate-200 hover:bg-slate-500/20 transition-colors">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<script>
const existingOptions = @json(old('options', $question->options->map(fn($opt) => ['id' => $opt->id, 'text' => $opt->text, 'is_correct' => $opt->is_correct])->toArray()));

function toggleOptions() {
    const type = document.getElementById('type').value;
    const optionsSection = document.getElementById('options-section');

    if (type === 'multiple_choice') {
        optionsSection.classList.remove('hidden');
        if (document.getElementById('options-list').children.length === 0) {
            loadOptions();
        }
    } else {
        optionsSection.classList.add('hidden');
    }
}

function loadOptions() {
    const optionsList = document.getElementById('options-list');
    optionsList.innerHTML = '';

    existingOptions.forEach((opt, index) => {
        const option = document.createElement('div');
        option.className = 'flex gap-3 items-end';
        option.innerHTML = `
            <div class="flex-1">
                <input type="text" name="options[${index}][text]"
                       class="w-full rounded-lg border border-slate-700 bg-slate-900 px-4 py-2 text-white placeholder-slate-500 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500/30"
                       placeholder="Texto de la opción"
                       value="${opt.text}"
                       required>
            </div>
            <label class="flex items-center gap-2 cursor-pointer whitespace-nowrap">
                <input type="checkbox" name="options[${index}][is_correct]" value="1"
                       class="rounded border-slate-600 bg-slate-900 text-emerald-500 focus:ring-2 focus:ring-emerald-500/30"
                       ${opt.is_correct ? 'checked' : ''}>
                <span class="text-sm text-slate-300">Correcta</span>
            </label>
            <input type="hidden" name="options[${index}][id]" value="${opt.id || ''}">
            <button type="button" onclick="this.parentElement.remove()"
                    class="text-rose-400 hover:text-rose-300 transition-colors">
                <i class="fas fa-trash"></i>
            </button>
        `;
        optionsList.appendChild(option);
    });
}

function addOption() {
    const optionsList = document.getElementById('options-list');
    const index = existingOptions.length + optionsList.children.length;

    const option = document.createElement('div');
    option.className = 'flex gap-3 items-end';
    option.innerHTML = `
        <div class="flex-1">
            <input type="text" name="options[${index}][text]"
                   class="w-full rounded-lg border border-slate-700 bg-slate-900 px-4 py-2 text-white placeholder-slate-500 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500/30"
                   placeholder="Texto de la opción"
                   required>
        </div>
        <label class="flex items-center gap-2 cursor-pointer whitespace-nowrap">
            <input type="checkbox" name="options[${index}][is_correct]" value="1"
                   class="rounded border-slate-600 bg-slate-900 text-emerald-500 focus:ring-2 focus:ring-emerald-500/30">
            <span class="text-sm text-slate-300">Correcta</span>
        </label>
        <button type="button" onclick="this.parentElement.remove()"
                class="text-rose-400 hover:text-rose-300 transition-colors">
            <i class="fas fa-trash"></i>
        </button>
    `;

    optionsList.appendChild(option);
}

document.addEventListener('DOMContentLoaded', toggleOptions);
</script>
@endsection
