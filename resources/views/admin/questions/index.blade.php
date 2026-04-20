@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-950 py-12 text-white">
    <div class="mx-auto flex max-w-7xl flex-col gap-10 px-6">
        <!-- Header -->
        <header class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.35em] text-sky-300">Gestión</p>
                <h1 class="mt-3 text-4xl font-semibold">Preguntas</h1>
                <p class="mt-2 max-w-2xl text-base text-slate-400">
                    Crea y administra las preguntas disponibles en la plataforma. Define tipos, categorías y puntuación.
                </p>
            </div>
            <a href="{{ route('admin.questions.create') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-sky-500/90 px-4 py-2 font-semibold text-white hover:bg-sky-400 transition-colors">
                <i class="fas fa-plus"></i>
                Nueva Pregunta
            </a>
        </header>

        <!-- Alerts -->
        @if (session('success'))
            <div class="rounded-lg bg-emerald-900/30 border border-emerald-600/50 p-4">
                <p class="text-sm text-emerald-200">{{ session('success') }}</p>
            </div>
        @endif

        <!-- Questions Table -->
        <section class="rounded-3xl border border-slate-800 bg-slate-900/60 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-800 text-sm">
                    <thead class="bg-slate-900/70 text-xs uppercase tracking-[0.35em] text-slate-400">
                        <tr>
                            <th class="px-4 py-4 text-left">Título</th>
                            <th class="px-4 py-4 text-left">Tipo</th>
                            <th class="px-4 py-4 text-left">Categoría</th>
                            <th class="px-4 py-4 text-center">Puntos</th>
                            <th class="px-4 py-4 text-center">Destacada</th>
                            <th class="px-4 py-4 text-left">Disponible hasta</th>
                            <th class="px-4 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/80">
                        @forelse ($questions as $question)
                            <tr class="hover:bg-slate-800/30 transition-colors">
                                <td class="px-4 py-4">
                                    <p class="font-semibold text-white truncate max-w-sm">{{ Str::limit($question->title, 50) }}</p>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-slate-700/50 text-slate-200">
                                        @if($question->type === 'multiple_choice')
                                            Opción múltiple
                                        @elseif($question->type === 'boolean')
                                            Sí/No
                                        @else
                                            Texto
                                        @endif
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    @if($question->category === 'predictive')
                                        <span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-blue-500/20 text-blue-300">
                                            Predictiva
                                        </span>
                                    @else
                                        <span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-emerald-500/20 text-emerald-300">
                                            Social
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-center text-amber-300 font-semibold">
                                    +{{ $question->points }}
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <input type="checkbox"
                                           class="toggle-featured rounded border-slate-600 bg-slate-800 text-sky-500 shadow-sm focus:border-sky-400 focus:ring focus:ring-sky-500/30"
                                           data-question-id="{{ $question->id }}"
                                           {{ $question->is_featured ? 'checked' : '' }}>
                                </td>
                                <td class="px-4 py-4 text-slate-400 text-xs">
                                    {{ $question->available_until ? \Carbon\Carbon::parse($question->available_until)->locale('es')->diffForHumans() : '—' }}
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center justify-end gap-3">
                                        <a href="{{ route('admin.questions.edit', $question) }}"
                                           class="text-sky-400 hover:text-sky-300 transition-colors"
                                           title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.questions.destroy', $question) }}"
                                              method="POST"
                                              class="inline"
                                              onsubmit="return confirm('¿Estás seguro de que deseas eliminar esta pregunta?')">
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
                                <td colspan="7" class="px-4 py-8 text-center text-slate-400">
                                    <i class="fas fa-inbox text-2xl mb-2 block"></i>
                                    No hay preguntas registradas. 
                                    <a href="{{ route('admin.questions.create') }}" class="text-sky-400 hover:text-sky-300">
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
        @if ($questions->hasPages())
            <div class="flex justify-center">
                {{ $questions->links() }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.toggle-featured').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                const questionId = this.dataset.questionId;
                const isFeatured = this.checked ? 1 : 0;

                fetch(`/admin/questions/${questionId}/toggle-featured`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ is_featured: isFeatured })
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        this.checked = !isFeatured;
                        const notification = document.createElement('div');
                        notification.className = 'fixed bottom-4 right-4 bg-rose-500 text-white px-4 py-2 rounded-lg shadow-lg';
                        notification.textContent = 'Error al actualizar el estado';
                        document.body.appendChild(notification);
                        setTimeout(() => notification.remove(), 3000);
                    }
                })
                .catch(error => {
                    this.checked = !isFeatured;
                    console.error('Error:', error);
                });
            });
        });
    });
</script>
@endpush

@endsection
