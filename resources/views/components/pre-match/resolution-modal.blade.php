<!-- Admin Resolution Modal Component -->
<div id="resolutionModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <!-- Header -->
        <div class="bg-gradient-to-r from-red-600 to-red-700 dark:from-red-900 dark:to-red-800 px-6 py-4 sticky top-0">
            <h2 class="text-2xl font-bold text-white">⚖️ Validar Acción</h2>
            <p class="text-red-100 text-sm mt-1">Admin: Determina si la acción se cumplió</p>
        </div>

        <div class="p-6 space-y-6">
            <!-- Información del Partido -->
            <div class="p-4 bg-gray-100 dark:bg-gray-700 rounded-lg">
                <span class="text-xs font-bold text-gray-600 dark:text-gray-300">PARTIDO</span>
                <p id="resolutionMatch" class="text-xl font-bold text-gray-900 dark:text-white mt-1">
                    Loading...
                </p>
            </div>

            <!-- Propuesta a Validar -->
            <div class="border-l-4 border-l-blue-500 pl-4">
                <span class="text-xs font-bold text-gray-600 dark:text-gray-300">ACCIÓN PROPUESTA</span>
                <p id="resolutionAction" class="text-lg font-bold text-gray-900 dark:text-white mt-1">
                    Loading...
                </p>
                <p id="resolutionDescription" class="text-sm text-gray-600 dark:text-gray-400 mt-2">

                </p>
            </div>

            <!-- Votación -->
            <div>
                <span class="text-xs font-bold text-gray-600 dark:text-gray-300 block mb-2">VOTACIÓN DEL GRUPO</span>
                <div id="resolutionVotes" class="flex gap-4">
                    <!-- Se agrega dinámicamente -->
                </div>
            </div>

            <!-- Score/Estadísticas -->
            <div class="grid grid-cols-2 gap-4">
                <div class="p-3 bg-green-50 dark:bg-green-900 rounded text-center">
                    <p class="text-xs text-green-600 dark:text-green-300">% CONFORME</p>
                    <p id="resolutionPercentage" class="text-2xl font-bold text-green-800 dark:text-green-200 mt-1">
                        0%
                    </p>
                </div>
                <div class="p-3 bg-blue-50 dark:bg-blue-900 rounded text-center">
                    <p class="text-xs text-blue-600 dark:text-blue-300">TOTAL VOTOS</p>
                    <p id="resolutionTotalVotes" class="text-2xl font-bold text-blue-800 dark:text-blue-200 mt-1">
                        0
                    </p>
                </div>
            </div>

            <!-- Formulario de Resolución -->
            <form id="resolutionForm" class="space-y-4 border-t pt-4">
                <!-- ¿Se cumplió? -->
                <div>
                    <label class="block text-sm font-bold mb-3 text-gray-700 dark:text-gray-300">
                        ¿Se cumplió la acción?
                    </label>
                    <div class="flex gap-3">
                        <label class="flex items-center gap-2 flex-1 p-3 border-2 rounded cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700"
                            id="fulfilledYes" style="border-color: #e5e7eb;">
                            <input type="radio" name="was_fulfilled" value="1" class="w-5 h-5">
                            <div>
                                <p class="font-bold text-gray-900 dark:text-white">✅ SÍ, Se Cumplió</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">La acción ocurrió en el partido</p>
                            </div>
                        </label>

                        <label class="flex items-center gap-2 flex-1 p-3 border-2 rounded cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700"
                            id="fulfilledNo" style="border-color: #e5e7eb;">
                            <input type="radio" name="was_fulfilled" value="0" class="w-5 h-5">
                            <div>
                                <p class="font-bold text-gray-900 dark:text-white">❌ NO, No Se Cumplió</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">La acción no ocurrió</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Notas del Admin -->
                <div>
                    <label class="block text-sm font-bold mb-2 text-gray-700 dark:text-gray-300">
                        Notas (evidencia, links, etc.) *
                    </label>
                    <textarea
                        name="admin_notes"
                        id="adminNotes"
                        placeholder="Ej: Video en 2:34 (gol de cabeza), Link: https://..."
                        class="w-full p-3 border dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded"
                        rows="3"
                        required
                    ></textarea>
                </div>

                <!-- Loser Selection (si No Se Cumplió) -->
                <div id="loserContainer" class="hidden">
                    <label class="block text-sm font-bold mb-2 text-gray-700 dark:text-gray-300">
                        ¿Quién pierde? (Propuesta Rechazada)
                    </label>
                    <select
                        name="loser_user_id"
                        id="loserSelect"
                        class="w-full p-3 border dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded"
                    >
                        <option value="">-- Selecciona --</option>
                        <!-- Se carga dinámicamente -->
                    </select>
                </div>

                <!-- Botones -->
                <div class="flex gap-2 pt-4">
                    <button
                        type="submit"
                        class="flex-1 bg-red-600 hover:bg-red-700 text-white py-3 rounded font-bold transition"
                    >
                        ✅ Validar Acción
                    </button>
                    <button
                        type="button"
                        onclick="closeResolutionModal()"
                        class="flex-1 bg-gray-400 hover:bg-gray-500 text-white py-3 rounded font-bold transition"
                    >
                        ❌ Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let currentResolution = {
        preMatchId: null,
        propositionId: null,
        propositionData: null
    };

    function openResolutionModal(preMatchId, propositionId, propositionData, propositionVotes) {
        currentResolution.preMatchId = preMatchId;
        currentResolution.propositionId = propositionId;
        currentResolution.propositionData = propositionData;

        // Cargar datos de la proposición
        document.getElementById('resolutionAction').textContent = propositionData.action;
        document.getElementById('resolutionDescription').textContent = propositionData.description || '';
        document.getElementById('resolutionMatch').textContent = propositionData.match_display;

        // Votos
        const votes = propositionVotes || [];
        const approvedCount = votes.filter(v => v.approved).length;
        const totalVotes = votes.length;
        const percentage = totalVotes > 0 ? Math.round((approvedCount / totalVotes) * 100) : 0;

        document.getElementById('resolutionPercentage').textContent = percentage + '%';
        document.getElementById('resolutionTotalVotes').textContent = totalVotes;

        const votesHtml = votes.map(v => `
            <div class="flex-1 p-3 rounded text-center">
                <p class="font-bold text-gray-900 dark:text-white">${v.user_name}</p>
                <p class="text-sm ${v.approved ? 'text-green-600 dark:text-green-300' : 'text-red-600 dark:text-red-300'}">
                    ${v.approved ? '✅ Sí' : '❌ No'}
                </p>
            </div>
        `).join('');
        document.getElementById('resolutionVotes').innerHTML = votesHtml;

        // Cargar participantes para seleccionar perdedor
        const participantsHtml = propositionData.group_members?.map(m => `
            <option value="${m.id}">${m.name}</option>
        `).join('');
        const loserSelect = document.getElementById('loserSelect');
        loserSelect.innerHTML = '<option value="">-- Selecciona --</option>' + participantsHtml;

        // Mostrar modal
        document.getElementById('resolutionModal').classList.remove('hidden');

        // Event listener para mostrar/ocultar loser container
        document.querySelectorAll('input[name="was_fulfilled"]').forEach(input => {
            input.addEventListener('change', function() {
                const loserContainer = document.getElementById('loserContainer');
                if (this.value === '0') {
                    loserContainer.classList.remove('hidden');
                } else {
                    loserContainer.classList.add('hidden');
                }
            });
        });
    }

    function closeResolutionModal() {
        document.getElementById('resolutionModal').classList.add('hidden');
        document.getElementById('resolutionForm').reset();
    }

    // Form submission
    document.getElementById('resolutionForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const wasFulfilled = document.querySelector('input[name="was_fulfilled"]:checked')?.value;
        if (!wasFulfilled) {
            alert('Debes seleccionar si se cumplió la acción');
            return;
        }

        const adminNotes = document.getElementById('adminNotes').value;
        const loserUserId = wasFulfilled === '0' ? document.getElementById('loserSelect').value : null;

        if (wasFulfilled === '0' && !loserUserId) {
            alert('Debes seleccionar quién pierde');
            return;
        }

        fetch(`/api/pre-matches/${currentResolution.preMatchId}/resolve`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('[name="_token"]').value
            },
            body: JSON.stringify({
                proposition_id: currentResolution.propositionId,
                was_fulfilled: wasFulfilled === '1',
                admin_notes: adminNotes,
                loser_user_id: loserUserId
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.message) {
                alert('✅ Acción validada!');
                closeResolutionModal();
                location.reload();
            }
        })
        .catch(err => {
            console.error('Error:', err);
            alert('❌ Error al validar');
        });
    });
</script>
