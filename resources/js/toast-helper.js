import Toastify from 'toastify-js';
import 'toastify-js/src/toastify.css';

/**
 * Mostrar toast de éxito
 */
export const showSuccessToast = (message, duration = 3000) => {
    Toastify({
        text: message,
        duration,
        gravity: 'top',
        position: 'right',
        backgroundColor: 'linear-gradient(135deg, #34d399, #10b981)',
        className: 'toast-success',
        stopOnFocus: true,
        close: true,
    }).showToast();
};

/**
 * Mostrar toast de error
 */
export const showErrorToast = (message, duration = 4000) => {
    Toastify({
        text: message,
        duration,
        gravity: 'top',
        position: 'right',
        backgroundColor: 'linear-gradient(135deg, #ef4444, #dc2626)',
        className: 'toast-error',
        stopOnFocus: true,
        close: true,
    }).showToast();
};

/**
 * Mostrar toast de información
 */
export const showInfoToast = (message, duration = 3000) => {
    Toastify({
        text: message,
        duration,
        gravity: 'top',
        position: 'right',
        backgroundColor: 'linear-gradient(135deg, #3b82f6, #2563eb)',
        className: 'toast-info',
        stopOnFocus: true,
        close: true,
    }).showToast();
};

/**
 * Mostrar toast de advertencia
 */
export const showWarningToast = (message, duration = 3500) => {
    Toastify({
        text: message,
        duration,
        gravity: 'top',
        position: 'right',
        backgroundColor: 'linear-gradient(135deg, #f59e0b, #d97706)',
        className: 'toast-warning',
        stopOnFocus: true,
        close: true,
    }).showToast();
};

/**
 * Función genérica para mostrar toast
 */
export const showToast = (message, type = 'info', duration) => {
    const durationMap = {
        success: 3000,
        error: 4000,
        info: 3000,
        warning: 3500,
    };

    const colorMap = {
        success: 'linear-gradient(135deg, #34d399, #10b981)',
        error: 'linear-gradient(135deg, #ef4444, #dc2626)',
        info: 'linear-gradient(135deg, #3b82f6, #2563eb)',
        warning: 'linear-gradient(135deg, #f59e0b, #d97706)',
    };

    Toastify({
        text: message,
        duration: duration || durationMap[type] || 3000,
        gravity: 'top',
        position: 'right',
        backgroundColor: colorMap[type] || colorMap.info,
        className: `toast-${type}`,
        stopOnFocus: true,
        close: true,
    }).showToast();
};

// Exponer globalmente para uso en templates inline
window.showSuccessToast = showSuccessToast;
window.showErrorToast = showErrorToast;
window.showInfoToast = showInfoToast;
window.showWarningToast = showWarningToast;
window.showToast = showToast;
