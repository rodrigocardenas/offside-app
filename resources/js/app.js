import './bootstrap';
import Alpine from 'alpinejs';
import '@fortawesome/fontawesome-free/js/all';

window.Alpine = Alpine;
Alpine.start();

// Sistema global de prevención de envíos duplicados
const formSubmissionTracker = {
    submissions: new Map(),

    isSubmitting(formId) {
        return this.submissions.get(formId) || false;
    },

    startSubmission(formId) {
        this.submissions.set(formId, true);
        // Limpiar después de 10 segundos por si acaso
        setTimeout(() => {
            this.submissions.delete(formId);
        }, 10000);
    },

    endSubmission(formId) {
        this.submissions.delete(formId);
    }
};

// Función global para prevenir envíos duplicados
function preventDuplicateSubmissions() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        const formId = form.id || `form_${Math.random().toString(36).substring(7)}`;
        form.id = formId;

        let isSubmitting = false;
        const submitButton = form.querySelector('button[type="submit"]');

        if (submitButton) {
            form.addEventListener('submit', async function(e) {
                if (formSubmissionTracker.isSubmitting(formId)) {
                    e.preventDefault();
                    return false;
                }

                formSubmissionTracker.startSubmission(formId);
                isSubmitting = true;
                submitButton.disabled = true;

                // Agregar clase de carga
                submitButton.classList.add('opacity-50', 'cursor-not-allowed');

                // Si el formulario tiene un campo de token, actualizarlo
                const tokenField = form.querySelector('input[name="_token"]');
                if (tokenField) {
                    tokenField.value = document.querySelector('meta[name="csrf-token"]').content;
                }

                try {
                    // Esperar a que se complete el envío
                    await new Promise(resolve => setTimeout(resolve, 100));
                } catch (error) {
                    console.error('Error en el envío del formulario:', error);
                } finally {
                    // Restaurar el botón después de 5 segundos si no hay respuesta
                    setTimeout(() => {
                        if (isSubmitting) {
                            isSubmitting = false;
                            submitButton.disabled = false;
                            submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
                            formSubmissionTracker.endSubmission(formId);
                        }
                    }, 5000);
                }
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
