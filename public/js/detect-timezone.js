/**
 * Auto-detectar zona horaria del navegador y guardarla en el servidor
 * Se ejecuta cuando la página carga
 */
document.addEventListener('DOMContentLoaded', function() {
    // Detectar zona horaria del navegador
    const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

    // Si ya está guardada, no hacer nada
    if (localStorage.getItem('lastDetectedTimezone') === timezone) {
        return;
    }

    // Guardar en localStorage para evitar múltiples requests
    localStorage.setItem('lastDetectedTimezone', timezone);

    // Enviar al servidor para guardar en BD
    fetch('/api/user/timezone', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify({ timezone: timezone })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('✓ Zona horaria guardada:', timezone);
            // Recargar la página para que se refleje la nueva zona
            window.location.reload();
        }
    })
    .catch(error => console.log('Timezone detection error:', error));
});
