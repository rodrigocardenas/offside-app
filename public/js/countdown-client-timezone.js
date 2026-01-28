/**
 * Cliente-side Countdown Timer con soporte para zonas horarias
 * Lee hora UTC del servidor y la muestra en zona horaria del cliente
 */

document.addEventListener('DOMContentLoaded', function() {
    updateAllCountdowns();
    setInterval(updateAllCountdowns, 1000);
});

function updateAllCountdowns() {
    const countdowns = document.querySelectorAll('.countdown[data-time-utc]');

    countdowns.forEach(element => {
        const utcTimeString = element.getAttribute('data-time-utc');
        if (utcTimeString) {
            updateCountdown(element, utcTimeString);
        }
    });
}

function updateCountdown(element, utcTimeString) {
    // Parsear la hora UTC del servidor
    const utcDate = new Date(utcTimeString + ' UTC');

    // Obtener hora actual del cliente
    const now = new Date();

    // Calcular diferencia en milisegundos
    const diff = utcDate - now;

    if (diff <= 0) {
        // Predicción cerrada
        element.textContent = '⏱️ Predicción cerrada';
        element.style.color = '#dc3545';
        return;
    }

    // Convertir a segundos, minutos, horas, días
    const totalSeconds = Math.floor(diff / 1000);
    const days = Math.floor(totalSeconds / 86400);
    const hours = Math.floor((totalSeconds % 86400) / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    // Formatear tiempo restante
    let timeString = '';
    if (days > 0) {
        timeString = `${days}d ${hours}h ${minutes}m`;
    } else if (hours > 0) {
        timeString = `${hours}h ${minutes}m ${seconds}s`;
    } else {
        timeString = `${minutes}m ${seconds}s`;
    }

    // Mostrar hora local del cliente como referencia (opcional)
    const clientLocalTime = new Intl.DateTimeFormat('es-ES', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
        timeZoneName: 'short'
    }).format(utcDate);

    element.textContent = `⏱️ ${timeString} (${clientLocalTime})`;
}
