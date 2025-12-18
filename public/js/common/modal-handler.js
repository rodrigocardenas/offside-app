/**
 * Modal Handler
 * Maneja la apertura y cierre de modales
 */

document.addEventListener('DOMContentLoaded', function() {
    const feedbackModal = document.getElementById('feedbackModal');
    
    if (!feedbackModal) return;

    const closeFeedbackModal = document.getElementById('closeFeedbackModal');
    const cancelFeedback = document.getElementById('cancelFeedback');
    
    // Función para abrir el modal
    window.openFeedbackModal = function() {
        feedbackModal.classList.add('active');
        document.body.style.overflow = 'hidden';
    };
    
    // Función para cerrar el modal
    window.closeFeedbackModal = function() {
        feedbackModal.classList.remove('active');
        document.body.style.overflow = '';
    };
    
    // Event listeners para cerrar
    if (closeFeedbackModal) {
        closeFeedbackModal.addEventListener('click', window.closeFeedbackModal);
    }
    
    if (cancelFeedback) {
        cancelFeedback.addEventListener('click', window.closeFeedbackModal);
    }
    
    // Cerrar al hacer clic fuera del modal
    feedbackModal.addEventListener('click', function(e) {
        if (e.target === feedbackModal) {
            window.closeFeedbackModal();
        }
    });
    
    console.log('✅ Modal handler cargado');
});
