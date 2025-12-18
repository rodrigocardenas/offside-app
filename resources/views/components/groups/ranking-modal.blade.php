@props([
    'groupId',
    'groupName' => 'Grupo'
])

<div id="ranking-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-2xl w-full max-w-md mx-4 max-h-[80vh] overflow-hidden shadow-2xl">
        {{-- Header --}}
        <div class="bg-gradient-to-r from-offside-primary to-offside-secondary p-6 text-white">
            <div class="flex justify-between items-center mb-2">
                <h3 class="text-xl font-bold flex items-center gap-2">
                    <i class="fas fa-trophy"></i>
                    Ranking Completo
                </h3>
                <button
                    onclick="closeRankingModal()"
                    class="text-white hover:text-gray-200 transition-colors">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            <p class="text-sm text-offside-light opacity-90">{{ $groupName }}</p>
        </div>

        {{-- Content --}}
        <div id="ranking-modal-content" class="overflow-y-auto max-h-[60vh] p-4">
            {{-- Loading state --}}
            <div id="ranking-loading" class="text-center py-8">
                <i class="fas fa-spinner fa-spin text-3xl text-offside-primary mb-2"></i>
                <p class="text-gray-600">Cargando ranking...</p>
            </div>

            {{-- Ranking list (will be populated by JS) --}}
            <div id="ranking-list" class="space-y-2 hidden"></div>
        </div>

        {{-- Footer with stats --}}
        <div id="ranking-stats" class="border-t border-gray-200 p-4 bg-gray-50 hidden">
            <div class="grid grid-cols-3 gap-4 text-center">
                <div>
                    <div class="text-xs text-gray-600 mb-1">Total Jugadores</div>
                    <div id="total-players" class="text-lg font-bold text-gray-800">-</div>
                </div>
                <div>
                    <div class="text-xs text-gray-600 mb-1">Tu Posición</div>
                    <div id="user-position" class="text-lg font-bold text-offside-primary">-</div>
                </div>
                <div>
                    <div class="text-xs text-gray-600 mb-1">Tus Puntos</div>
                    <div id="user-points" class="text-lg font-bold text-offside-primary">-</div>
                </div>
            </div>
        </div>
    </div>
</div>
