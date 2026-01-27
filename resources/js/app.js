import './bootstrap';
import Alpine from 'alpinejs';
import '@fortawesome/fontawesome-free/js/all';
import './header-dropdown';
import './navigation';
import './android-back-button';

window.Alpine = Alpine;

// Inicializar Alpine después de que el DOM esté completamente cargado
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        Alpine.start();
    });
} else {
    Alpine.start();
}

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
    const forms = document.querySelectorAll('form:not(#chatForm)');
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

// Inicializar prevención de duplicados
document.addEventListener('DOMContentLoaded', function() {
    preventDuplicateSubmissions();
});
