<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Admin Dashboard') }}
        </h2>
    </x-slot>


    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    {{ session('status') }}
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Questions Card -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-300 mr-4">
                                <i class="fas fa-question-circle text-xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Preguntas</h3>
                        </div>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            Administra todas las preguntas del sistema.
                        </p>
                        <div class="mt-4">
                            <a href="{{ route('admin.questions.index') }}" 
                               class="inline-flex items-center px-4 py-2 bg-indigo-600 dark:bg-indigo-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 dark:hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Gestionar Preguntas
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Template Questions Card -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-300 mr-4">
                                <i class="fas fa-clipboard-list text-xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Plantillas de Preguntas</h3>
                        </div>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            Administra las plantillas de preguntas para partidos.
                        </p>
                        <div class="mt-4">
                            <a href="{{ route('admin.template-questions.index') }}" 
                               class="inline-flex items-center px-4 py-2 bg-green-600 dark:bg-green-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 dark:hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Gestionar Plantillas
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
