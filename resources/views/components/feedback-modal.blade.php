<!-- Modal de Feedback -->
<div id="feedbackModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-offside-dark rounded-lg p-6 w-full max-w-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold">Envíanos tu opinión</h3>
            <button id="closeFeedbackModal" onclick="document.getElementById('feedbackModal').classList.add('hidden')" class="text-offside-light hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form id="feedbackForm" action="{{ route('feedback.store') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label for="type" class="block text-sm font-medium mb-2">Tipo de comentario</label>
                <select id="type" name="type" class="w-full bg-offside-primary bg-opacity-20 border border-offside-primary rounded-md p-2 text-white">
                    <option value="suggestion">Sugerencia</option>
                    <option value="bug">Reportar un error</option>
                    <option value="compliment">Elogio</option>
                    <option value="other">Otro</option>
                </select>
            </div>
            <div class="mb-4">
                <label for="message" class="block text-sm font-medium mb-2">Mensaje</label>
                <textarea id="message" name="message" rows="4" class="w-full bg-offside-primary bg-opacity-20 border border-offside-primary rounded-md p-2 text-white" required></textarea>
            </div>
            <div class="mb-4 flex items-center">
                <input type="checkbox" id="is_anonymous" name="is_anonymous" class="rounded border-offside-primary bg-offside-primary bg-opacity-20 text-offside-primary focus:ring-offside-primary">
                <label for="is_anonymous" class="ml-2 text-sm">Enviar como anónimo</label>
            </div>
            <div class="flex justify-end space-x-2">
                <button type="button" id="cancelFeedback" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-offside-primary text-white rounded-md hover:bg-offside-primary/90">Enviar</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Abrir modal
    document.getElementById('openFeedbackModal').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('feedbackModal').classList.remove('hidden');
    });

    // Cerrar modal
    document.getElementById('closeFeedbackModal').addEventListener('click', function() {
        document.getElementById('feedbackModal').classList.add('hidden');
    });

    // Cancelar formulario
    document.getElementById('cancelFeedback').addEventListener('click', function() {
        document.getElementById('feedbackModal').classList.add('hidden');
        document.getElementById('feedbackForm').reset();
    });
});
</script>
