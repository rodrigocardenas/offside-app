<!-- Modal de Feedback -->
<div id="feedbackModal" class="modal-overlay">
    <div class="modal-content" style="background: var(--dark-bg-secondary, #fff); color: var(--dark-text-primary, #333);">
        <div class="modal-header" style="border-bottom-color: var(--dark-border, #e0e0e0);">
            <h3 class="modal-title" style="color: var(--dark-text-primary, #333);">Envíanos tu opinión</h3>
            <button id="closeFeedbackModal" class="modal-close" type="button" style="color: var(--dark-text-secondary, #666);">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="feedbackForm" action="{{ route('feedback.store') }}" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label for="type" style="color: var(--dark-text-primary, #333);">Tipo de comentario</label>
                    <select id="type" name="type" style="background: var(--dark-bg-tertiary, white); color: var(--dark-text-primary, #333); border-color: var(--dark-border, #e0e0e0);">
                        <option value="suggestion">Sugerencia</option>
                        <option value="bug">Reportar un error</option>
                        <option value="compliment">Elogio</option>
                        <option value="other">Otro</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="message" style="color: var(--dark-text-primary, #333);">Mensaje</label>
                    <textarea id="message" name="message" rows="4" minlength="10" required style="background: var(--dark-bg-tertiary, white); color: var(--dark-text-primary, #333); border-color: var(--dark-border, #e0e0e0);"></textarea>
                </div>

                <div class="form-group" style="display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" id="is_anonymous" name="is_anonymous" style="width: auto; margin: 0;">
                    <label for="is_anonymous" style="margin: 0; color: var(--dark-text-primary, #333);">Enviar como anónimo</label>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" id="cancelFeedback" class="btn-secondary" style="background: var(--dark-bg-tertiary, #f0f0f0); color: var(--dark-text-primary, #333); border: 1px solid var(--dark-border, #e0e0e0);">Cancelar</button>
                <button type="submit" class="btn-primary">Enviar</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('feedbackModal');
    const closeBtn = document.getElementById('closeFeedbackModal');
    const cancelBtn = document.getElementById('cancelFeedback');
    const form = document.getElementById('feedbackForm');

    // Función para abrir modal
    window.openFeedbackModal = function(e) {
        if (e) e.preventDefault();
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    // Función para cerrar modal
    function closeFeedbackModal() {
        modal.classList.remove('active');
        document.body.style.overflow = '';
        form.reset();
    }

    // Event listeners
    if (closeBtn) closeBtn.addEventListener('click', closeFeedbackModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeFeedbackModal);

    // Cerrar al hacer click en el overlay
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeFeedbackModal();
        }
    });

    // Enviar formulario
    form.addEventListener('submit', function(e) {
        // El formulario se enviará normalmente
        console.log('📤 Enviando feedback...');
    });
});
</script>
