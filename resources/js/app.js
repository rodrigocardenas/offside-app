import './bootstrap';
import Alpine from 'alpinejs';
import '@fortawesome/fontawesome-free/js/all';

window.Alpine = Alpine;
Alpine.start();

// Prevenir envíos múltiples del formulario de chat
document.addEventListener('DOMContentLoaded', function() {
    const chatForm = document.getElementById('chatForm');
    if (chatForm) {
        const submitButton = chatForm.querySelector('button[type="submit"]');
        const inputField = chatForm.querySelector('input[name="message"]');
        let isSubmitting = false;

        chatForm.addEventListener('submit', function(e) {
            if (isSubmitting) {
                e.preventDefault();
                return;
            }

            isSubmitting = true;
            submitButton.disabled = true;
            submitButton.classList.add('opacity-50', 'cursor-not-allowed');

            // Restaurar el botón después de 5 segundos en caso de error
            setTimeout(() => {
                isSubmitting = false;
                submitButton.disabled = false;
                submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
            }, 5000);
        });

        // Prevenir envío con Enter múltiple
        inputField.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && isSubmitting) {
                e.preventDefault();
            }
        });
    }
});
