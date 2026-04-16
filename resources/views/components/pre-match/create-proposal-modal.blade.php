<!-- Create Proposal Modal Component -->
<div id="createProposalModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg max-w-2xl w-full">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 dark:from-blue-900 dark:to-blue-800 px-6 py-4 rounded-t-lg">
            <h2 class="text-2xl font-bold text-white">💡 Proponer Acción</h2>
            <p class="text-blue-100 text-sm mt-1">Crea una propuesta para el Pre Match Challenge</p>
        </div>

        <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
            <form id="createProposalForm" class="space-y-4">
                @csrf
                <input type="hidden" name="pre_match_id" id="preMatchId">

                <!-- Acción Libre + Sugerir -->
                <div>
                    <label class="block text-sm font-bold mb-2 text-gray-700 dark:text-gray-300">
                        Tu acción (texto libre)
                    </label>
                    <div class="flex gap-2">
                        <textarea
                            name="action"
                            id="actionInput"
                            placeholder="Ej: 3 goles de cabeza, penal atajado, 5+ tarjetas..."
                            class="flex-1 p-3 border dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded font-mono text-sm resize-none"
                            rows="3"
                            required
                        ></textarea>
                        <div class="flex flex-col gap-2 h-fit">
                            <button
                                type="button"
                                onclick="suggestRandomAction(document.getElementById('preMatchId').value)"
                                id="suggestBtn"
                                class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded font-bold text-xs whitespace-nowrap transition"
                            >
                                🎲 Sugerir
                            </button>
                            <button
                                type="button"
                                onclick="openMatchActionsModal()"
                                id="matchActionsBtn"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded font-bold text-xs whitespace-nowrap transition"
                            >
                                📋 Acciones
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Sugerencia Mostrada -->
                <div id="suggestionContainer" class="hidden p-4 bg-green-50 dark:bg-green-900 border-l-4 border-l-green-500 rounded">
                    <p class="text-sm font-bold text-green-800 dark:text-green-200 mb-2">
                        💡 Sugerencia Auto-Generada:
                    </p>
                    <p id="suggestedAction" class="text-lg font-bold text-gray-900 dark:text-white mb-2">

                    </p>
                    <div id="suggestionTags" class="flex gap-2 mb-3 text-xs">
                        <!-- Los tags se agrega dinámicamente -->
                    </div>

                    <div class="flex gap-2">
                        <button
                            type="button"
                            onclick="acceptSuggestion()"
                            class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2 rounded font-bold text-sm transition"
                        >
                            ✅ Aceptar Sugerencia
                        </button>
                        <button
                            type="button"
                            onclick="suggestRandomAction(document.getElementById('preMatchId').value)"
                            class="flex-1 bg-gray-400 hover:bg-gray-500 text-white py-2 rounded font-bold text-sm transition"
                        >
                            🔄 Otra Sugerencia
                        </button>
                    </div>
                </div>

                <!-- Descripción Opcional -->
                <div>
                    <label class="block text-sm font-bold mb-2 text-gray-700 dark:text-gray-300">
                        Descripción (opcional)
                    </label>
                    <textarea
                        name="description"
                        id="descriptionInput"
                        placeholder="Ej: Es raro pero posible según estadísticas..."
                        class="w-full p-3 border dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded resize-none"
                        rows="2"
                    ></textarea>
                </div>

                <!-- Botones de Acción -->
                <div class="flex gap-2 pt-4">
                    <button
                        type="submit"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-3 rounded font-bold transition"
                    >
                        ✅ Crear Propuesta
                    </button>
                    <button
                        type="button"
                        onclick="closeCreateProposalModal()"
                        class="flex-1 bg-gray-400 hover:bg-gray-500 text-white py-3 rounded font-bold transition"
                    >
                        ❌ Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Match Actions Modal -->
<div id="matchActionsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg max-w-2xl w-full max-h-[80vh] flex flex-col">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 dark:from-blue-900 dark:to-blue-800 px-6 py-4 rounded-t-lg">
            <h2 class="text-2xl font-bold text-white">📋 Acciones de Partido Predefinidas</h2>
            <p class="text-blue-100 text-sm mt-1">Selecciona una acción común para tu propuesta</p>
        </div>

        <!-- Content -->
        <div class="p-6 flex-1 overflow-y-auto">
            <div id="actionsLoading" style="display: none;" class="text-center py-4">
                <p class="text-gray-600 dark:text-gray-400">⏳ Cargando acciones...</p>
            </div>
            <div id="actionsContainer" class="space-y-2">
                <!-- Las acciones se cargan dinámicamente aquí -->
            </div>
        </div>

        <!-- Footer -->
        <div class="border-t dark:border-gray-700 px-6 py-4 flex gap-2 justify-end">
            <button
                type="button"
                onclick="closeMatchActionsModal()"
                class="px-6 py-2 bg-gray-400 hover:bg-gray-500 text-white rounded font-bold transition"
            >
                ❌ Cerrar
            </button>
        </div>
    </div>
</div>

<script>
    let currentSuggestion = null;

    function openProposalModal(preMatchId) {
        document.getElementById('preMatchId').value = preMatchId;
        document.getElementById('createProposalModal').classList.remove('hidden');
    }

    function closeCreateProposalModal() {
        document.getElementById('createProposalModal').classList.add('hidden');
        document.getElementById('createProposalForm').reset();
        document.getElementById('suggestionContainer').classList.add('hidden');
    }

    function suggestRandomAction(preMatchId) {
        document.getElementById('suggestBtn').disabled = true;
        document.getElementById('suggestBtn').textContent = '⏳ Cargando...';

        fetch('/api/action-templates/random')
            .then(res => res.json())
            .then(data => {
                currentSuggestion = data;
                document.getElementById('suggestedAction').textContent = data.action;

                const tags = document.getElementById('suggestionTags');
                tags.innerHTML = `
                    <span class="bg-blue-200 dark:bg-blue-800 text-blue-900 dark:text-blue-200 px-2 py-1 rounded">
                        ${data.category}
                    </span>
                    <span class="bg-purple-200 dark:bg-purple-800 text-purple-900 dark:text-purple-200 px-2 py-1 rounded">
                        Probabilidad: ${(data.probability * 100).toFixed(0)}%
                    </span>
                `;

                document.getElementById('suggestionContainer').classList.remove('hidden');
                document.getElementById('suggestBtn').disabled = false;
                document.getElementById('suggestBtn').textContent = '🎲 Sugerir';
            })
            .catch(err => {
                console.error('Error fetching suggestion:', err);
                document.getElementById('suggestBtn').disabled = false;
                document.getElementById('suggestBtn').textContent = '🎲 Sugerir';
            });
    }

    function acceptSuggestion() {
        if (currentSuggestion) {
            document.getElementById('actionInput').value = currentSuggestion.action;
            if (currentSuggestion.description) {
                document.getElementById('descriptionInput').value = currentSuggestion.description;
            }
        }
    }

    // ============ MATCH ACTIONS MODAL ============
    function openMatchActionsModal() {
        const modal = document.getElementById('matchActionsModal');
        if (!modal) {
            console.error('❌ Match Actions Modal no encontrado');
            return;
        }
        
        modal.classList.remove('hidden');
        loadMatchActions();
    }

    function closeMatchActionsModal() {
        const modal = document.getElementById('matchActionsModal');
        if (modal) modal.classList.add('hidden');
    }

    async function loadMatchActions() {
        try {
            document.getElementById('actionsLoading').style.display = 'block';
            document.getElementById('actionsContainer').innerHTML = '';

            const response = await fetch('/api/match-actions', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'include'
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const actions = await response.json();

            // Agrupar por categoría
            const grouped = {};
            actions.forEach(action => {
                if (!grouped[action.category]) grouped[action.category] = [];
                grouped[action.category].push(action);
            });

            // Renderizar categorías
            const container = document.getElementById('actionsContainer');
            Object.entries(grouped).forEach(([category, items]) => {
                const categoryEl = document.createElement('div');
                categoryEl.className = 'mb-4';
                categoryEl.innerHTML = `<h4 class="font-bold text-gray-700 dark:text-gray-300 mb-2">${getCategoryLabel(category)}</h4>`;

                items.forEach(action => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'w-full text-left p-2 mb-2 bg-gray-100 dark:bg-gray-700 hover:bg-blue-100 dark:hover:bg-blue-600 rounded text-sm transition';
                    button.innerHTML = `<span>${action.icon}</span> <strong>${action.title}</strong><br><small class="text-gray-600 dark:text-gray-400">${action.description}</small>`;
                    button.onclick = () => selectMatchAction(action);
                    categoryEl.appendChild(button);
                });

                container.appendChild(categoryEl);
            });

            document.getElementById('actionsLoading').style.display = 'none';
        } catch (error) {
            console.error('❌ Error loading match actions:', error);
            document.getElementById('actionsLoading').style.display = 'none';
            document.getElementById('actionsContainer').innerHTML = '<p class="text-red-600">Error al cargar acciones</p>';
        }
    }

    function getCategoryLabel(category) {
        const labels = {
            'goal': '⚽ Goles y Anotaciones',
            'condition': '📊 Condiciones de Partido',
            'event': '⚡ Eventos del Partido',
            'timing': '⏱️ Tiempo y Ritmo',
            'default': category
        };
        return labels[category] || labels['default'];
    }

    function selectMatchAction(action) {
        document.getElementById('actionInput').value = action.title;
        closeMatchActionsModal();

        // Incrementar popularidad
        fetch(`/api/match-actions/${action.id}/popularity`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('[name="_token"]').value
            }
        }).catch(err => console.log('Popularidad actualizada'));
    }

    // Form submission
    document.getElementById('createProposalForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const preMatchId = document.getElementById('preMatchId').value;
        const action = document.getElementById('actionInput').value;
        const description = document.getElementById('descriptionInput').value;

        fetch(`/api/pre-matches/${preMatchId}/propositions`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('[name="_token"]').value
            },
            body: JSON.stringify({ action, description })
        })
        .then(res => res.json())
        .then(data => {
            if (data.message) {
                alert('✅ Propuesta creada!');
                closeCreateProposalModal();
                location.reload();
            }
        })
        .catch(err => {
            console.error('Error:', err);
            alert('❌ Error al crear propuesta');
        });
    });

</script>
