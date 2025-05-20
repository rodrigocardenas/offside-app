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

        chatForm.addEventListener('submit', async function(e) {
            e.preventDefault(); // Prevenir el envío por defecto

            if (isSubmitting) {
                return;
            }

            isSubmitting = true;
            submitButton.disabled = true;
            submitButton.classList.add('opacity-50', 'cursor-not-allowed');

            try {
                const formData = new FormData(chatForm);
                const response = await fetch(chatForm.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (response.ok) {
                    // Limpiar el campo de mensaje
                    inputField.value = '';
                    // Recargar la página para mostrar el nuevo mensaje
                    window.location.reload();
                } else {
                    throw new Error('Error al enviar el mensaje');
                }
            } catch (error) {
                console.error('Error:', error);
                // Restaurar el botón en caso de error
                isSubmitting = false;
                submitButton.disabled = false;
                submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        });

        // Prevenir envío con Enter múltiple
        inputField.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && isSubmitting) {
                e.preventDefault();
            }
        });
    }
});
