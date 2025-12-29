<!-- Modal de Feedback -->
<div id="feedbackModal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.6); display: none; align-items: center; justify-content: center; z-index: 9999; padding: 16px;">
    <div style="background: {{ $bgSecondary ?? '#2a2a2a' }}; border: 1px solid {{ $borderColor ?? '#333' }}; border-radius: 16px; width: 100%; max-width: 500px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);">
        <!-- Header -->
        <div style="padding: 20px 24px; border-bottom: 1px solid {{ $borderColor ?? '#333' }}; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 18px; font-weight: 700; color: {{ $textPrimary ?? '#ffffff' }};">Envíanos tu opinión</h3>
            <button id="closeFeedbackModal" type="button" style="background: none; border: none; font-size: 24px; color: {{ $textSecondary ?? '#b0b0b0' }}; cursor: pointer; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 6px; transition: all 0.2s ease;"
                    onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='none'">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Body -->
        <form id="feedbackForm" action="{{ route('feedback.store') }}" method="POST" style="display: flex; flex-direction: column; padding: 24px;">
            @csrf

            <!-- Tipo de comentario -->
            <div style="margin-bottom: 20px;">
                <label for="type" style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px; color: {{ $textPrimary ?? '#ffffff' }};">Tipo de comentario</label>
                <select id="type" name="type" required
                        style="width: 100%; padding: 10px 12px; border: 1px solid {{ $borderColor ?? '#333' }}; border-radius: 8px; background: {{ $bgTertiary ?? '#333333' }}; color: {{ $textPrimary ?? '#ffffff' }}; font-size: 14px; font-family: inherit;">
                    <option value="suggestion" style="background: {{ $bgTertiary ?? '#333333' }}; color: {{ $textPrimary ?? '#ffffff' }};">Sugerencia</option>
                    <option value="bug" style="background: {{ $bgTertiary ?? '#333333' }}; color: {{ $textPrimary ?? '#ffffff' }};">Reportar un error</option>
                    <option value="compliment" style="background: {{ $bgTertiary ?? '#333333' }}; color: {{ $textPrimary ?? '#ffffff' }};">Elogio</option>
                    <option value="other" style="background: {{ $bgTertiary ?? '#333333' }}; color: {{ $textPrimary ?? '#ffffff' }};">Otro</option>
                </select>
            </div>

            <!-- Mensaje -->
            <div style="margin-bottom: 20px;">
                <label for="message" style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px; color: {{ $textPrimary ?? '#ffffff' }};">Mensaje</label>
                <textarea id="message" name="message" rows="5" minlength="10" required
                          style="width: 100%; padding: 12px; border: 1px solid {{ $borderColor ?? '#333' }}; border-radius: 8px; background: {{ $bgTertiary ?? '#333333' }}; color: {{ $textPrimary ?? '#ffffff' }}; font-size: 14px; font-family: inherit; resize: vertical; box-sizing: border-box;"></textarea>
            </div>

            <!-- Anónimo -->
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 24px;">
                <input type="checkbox" id="is_anonymous" name="is_anonymous" style="width: 18px; height: 18px; cursor: pointer; accent-color: {{ $accentColor ?? '#00deb0' }};">
                <label for="is_anonymous" style="margin: 0; font-size: 14px; color: {{ $textSecondary ?? '#b0b0b0' }}; cursor: pointer;">Enviar como anónimo</label>
            </div>

            <!-- Botones -->
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" id="cancelFeedback" style="padding: 10px 20px; border: 1px solid {{ $borderColor ?? '#333' }}; border-radius: 8px; background: transparent; color: {{ $textSecondary ?? '#b0b0b0' }}; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s ease;"
                        onmouseover="this.style.background='rgba(255,255,255,0.05)'" onmouseout="this.style.background='transparent'">
                    Cancelar
                </button>
                <button type="submit" style="padding: 10px 20px; border: none; border-radius: 8px; background: {{ $accentColor ?? '#00deb0' }}; color: #000; font-size: 14px; font-weight: 700; cursor: pointer; transition: all 0.2s ease;"
                        onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                    Enviar
                </button>
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
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    };

    // Función para cerrar modal
    window.closeFeedbackModal = function() {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        form.reset();
    };

    // Event listeners
    if (closeBtn) closeBtn.addEventListener('click', window.closeFeedbackModal);
    if (cancelBtn) cancelBtn.addEventListener('click', window.closeFeedbackModal);

    // Cerrar al hacer click en el overlay
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            window.closeFeedbackModal();
        }
    });

    // Enviar formulario
    form.addEventListener('submit', function(e) {
        console.log('📤 Enviando feedback...');
    });
});
</script>
