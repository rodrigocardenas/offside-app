@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-950 py-12 text-white">
    <div class="mx-auto flex max-w-3xl flex-col gap-10 px-6">
        <!-- Header -->
        <header>
            <a href="{{ route('admin.template-questions.index') }}" class="inline-flex items-center gap-2 text-amber-400 hover:text-amber-300 mb-4">
                <i class="fas fa-arrow-left"></i>
                Volver a Plantillas
            </a>
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.35em] text-amber-300">Nueva Plantilla</p>
                <h1 class="mt-3 text-4xl font-semibold">Crear Plantilla de Pregunta</h1>
                <p class="mt-2 text-base text-slate-400">Define una plantilla reutilizable para preguntas de partidos frecuentes.</p>
            </div>
        </header>

        <!-- Form -->
        <form action="{{ route('admin.template-questions.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Text -->
            <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6">
                <label for="text" class="block text-sm font-semibold text-white mb-2">Texto de la Plantilla *</label>
                <textarea id="text" name="text" rows="3" class="w-full rounded-lg border border-slate-700 bg-slate-900 px-4 py-3 text-white placeholder-slate-500 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/30" placeholder="Ej: ¿Quién marcará el próximo gol?" required>{{ old('text') }}</textarea>
                @error('text')<p class="mt-2 text-sm text-rose-400">{{ $message }}</p>@enderror
            </div>

            <!-- Type -->
            <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6">
                <label for="type" class="block text-sm font-semibold text-white mb-2">Tipo de Pregunta *</label>
                <select id="type" name="type" oninput="toggleOptions()" class="w-full rounded-lg border border-slate-700 bg-slate-900 px-4 py-3 text-white focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/30" required>
                    <option value="">Selecciona un tipo</option>
                    <option value="multiple_choice" {{ old('type') === 'multiple_choice' ? 'selected' : '' }}>Opción múltiple</option>
                    <option value="boolean" {{ old('type') === 'boolean' ? 'selected' : '' }}>Verdadero/Falso</option>
                    <option value="text" {{ old('type') === 'text' ? 'selected' : '' }}>Texto</option>
                </select>
                @error('type')<p class="mt-2 text-sm text-rose-400">{{ $message }}</p>@enderror
            </div>

            <!-- Featured -->
            <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" id="is_featured" name="is_featured" value="1" class="rounded border-slate-600 bg-slate-900 text-amber-500 focus:ring-2 focus:ring-amber-500/30" {{ old('is_featured') ? 'checked' : '' }}>
                    <span class="font-semibold text-white">Destacar esta plantilla</span>
                </label>
            </div>

            <!-- Options (for multiple choice) -->
            <div id="options-section" class="hidden rounded-2xl border border-slate-800 bg-slate-900/60 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-white">Opciones</h3>
                    <button type="button" onclick="addOption()" class="text-amber-400 hover:text-amber-300 text-sm font-semibold">
                        <i class="fas fa-plus mr-1"></i>Agregar opción
                    </button>
                </div>
                <div id="options-list" class="space-y-4"></div>
            </div>

            <!-- Buttons -->
            <div class="flex gap-3 pt-6">
                <button type="submit" class="flex-1 rounded-lg bg-amber-500/90 px-6 py-3 font-semibold text-white hover:bg-amber-400 transition-colors">
                    <i class="fas fa-check mr-2"></i>Crear Plantilla
                </button>
                <a href="{{ route('admin.template-questions.index') }}" class="flex-1 rounded-lg border border-slate-600/40 bg-slate-500/10 px-6 py-3 text-center font-semibold text-slate-200 hover:bg-slate-500/20 transition-colors">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
function toggleOptions() {
    const type = document.getElementById('type').value;
    const optionsSection = document.getElementById('options-section');
    if (type === 'multiple_choice') {
        optionsSection.classList.remove('hidden');
        if (document.getElementById('options-list').children.length === 0) {
            addOption();
            addOption();
        }
    } else {
        optionsSection.classList.add('hidden');
    }
}

function addOption() {
    const optionsList = document.getElementById('options-list');
    const index = optionsList.children.length;
    const option = document.createElement('div');
    option.className = 'flex gap-3 items-end';
    option.innerHTML = `
        <div class="flex-1">
            <input type="text" name="options[${index}][text]" class="w-full rounded-lg border border-slate-700 bg-slate-900 px-4 py-2 text-white placeholder-slate-500 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/30" placeholder="Texto de la opción" required>
        </div>
        <label class="flex items-center gap-2 cursor-pointer whitespace-nowrap">
            <input type="checkbox" name="options[${index}][is_correct]" value="1" class="rounded border-slate-600 bg-slate-900 text-emerald-500 focus:ring-2 focus:ring-emerald-500/30">
            <span class="text-sm text-slate-300">Correcta</span>
        </label>
        <button type="button" onclick="this.parentElement.remove()" class="text-rose-400 hover:text-rose-300 transition-colors">
            <i class="fas fa-trash"></i>
        </button>
    `;
    optionsList.appendChild(option);
}

document.addEventListener('DOMContentLoaded', toggleOptions);
</script>
@endsection
