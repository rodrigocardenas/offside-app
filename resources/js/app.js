import './bootstrap';
import Alpine from 'alpinejs';
import '@fortawesome/fontawesome-free/js/all';

window.Alpine = Alpine;
Alpine.start();

// Función global para prevenir envíos duplicados
function preventDuplicateSubmissions() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        let isSubmitting = false;
        const submitButton = form.querySelector('button[type="submit"]');

        if (submitButton) {
            form.addEventListener('submit', function(e) {
                if (isSubmitting) {
                    e.preventDefault();
                    return false;
                }

                isSubmitting = true;
                submitButton.disabled = true;

                // Restaurar el botón después de 5 segundos si no hay respuesta
                setTimeout(() => {
                    if (isSubmitting) {
                        isSubmitting = false;
                        submitButton.disabled = false;
                    }
                }, 5000);
            });
        }
    });
}

// Prevenir envíos múltiples del formulario de chat
document.addEventListener('DOMContentLoaded', function() {
    // Aplicar prevención de duplicados a todos los formularios
    preventDuplicateSubmissions();

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
