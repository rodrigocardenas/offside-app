@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-950 py-12 text-white">
    <div class="mx-auto flex max-w-7xl flex-col gap-10 px-6">
        <!-- Header -->
        <header class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.35em] text-amber-300">Gestión</p>
                <h1 class="mt-3 text-4xl font-semibold">Plantillas de Preguntas</h1>
                <p class="mt-2 max-w-2xl text-base text-slate-400">
                    Crea y administra plantillas de preguntas para partidos. Reutiliza estructuras comunes.
                </p>
            </div>
            <a href="{{ route('admin.template-questions.create') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-amber-500/90 px-4 py-2 font-semibold text-white hover:bg-amber-400 transition-colors">
                <i class="fas fa-plus"></i>
                Nueva Plantilla
            </a>
        </header>

        <!-- Alerts -->
        @if (session('success'))
            <div class="rounded-lg bg-emerald-900/30 border border-emerald-600/50 p-4">
                <p class="text-sm text-emerald-200">{{ session('success') }}</p>
            </div>
        @endif

        <!-- Templates Table -->
        <section class="rounded-3xl border border-slate-800 bg-slate-900/60 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-800 text-sm">
                    <thead class="bg-slate-900/70 text-xs uppercase tracking-[0.35em] text-slate-400">
                        <tr>
                            <th class="px-4 py-4 text-left">Tipo</th>
                            <th class="px-4 py-4 text-left">Texto</th>
                            <th class="px-4 py-4 text-center">Destacada</th>
                            <th class="px-4 py-4 text-left">Opciones</th>
                            <th class="px-4 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/80">
                        @forelse ($templateQuestions as $template)
                            <tr class="hover:bg-slate-800/30 transition-colors">
                                <td class="px-4 py-4">
                                    <span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-slate-700/50 text-slate-200">
                                        @if($template->type === 'multiple_choice')
                                            Opción múltiple
                                        @elseif($template->type === 'boolean')
                                            Sí/No
                                        @else
                                            Texto
                                        @endif
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <p class="font-semibold text-white truncate max-w-xs">{{ Str::limit($template->text, 50) }}</p>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    @if($template->is_featured)
                                        <span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-amber-500/20 text-amber-300">
                                            <i class="fas fa-star mr-1"></i>Sí
                                        </span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-slate-400 text-xs">
                                    @if($template->type === 'multiple_choice' && $template->options)
                                        <div class="space-y-1">
                                            @foreach($template->options as $opt)
                                                <div class="{{ isset($opt['is_correct']) && $opt['is_correct'] ? 'text-emerald-300 font-semibold' : '' }}">
                                                    {{ Str::limit($opt['text'] ?? '', 30) }}
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center justify-end gap-3">
                                        <a href="{{ route('admin.template-questions.edit', $template) }}"
                                           class="text-amber-400 hover:text-amber-300 transition-colors"
                                           title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.template-questions.destroy', $template) }}"
                                              method="POST"
                                              class="inline"
                                              onsubmit="return confirm('¿Estás seguro de que deseas eliminar esta plantilla?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="text-rose-400 hover:text-rose-300 transition-colors"
                                                    title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-slate-400">
                                    <i class="fas fa-inbox text-2xl mb-2 block"></i>
                                    No hay plantillas registradas. 
                                    <a href="{{ route('admin.template-questions.create') }}" class="text-amber-400 hover:text-amber-300">
                                        Crea la primera
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Pagination -->
        @if ($templateQuestions->hasPages())
            <div class="flex justify-center">
                {{ $templateQuestions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
