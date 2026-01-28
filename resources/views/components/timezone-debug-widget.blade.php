<!-- Debug: Timezone Sync Status -->
<div id="tzDebugWidget" style="
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: rgba(0, 0, 0, 0.9);
    border: 2px solid #00deb0;
    border-radius: 8px;
    padding: 12px;
    max-width: 300px;
    font-family: monospace;
    font-size: 12px;
    color: #00deb0;
    z-index: 9998;
    display: none;
    box-shadow: 0 0 20px rgba(0, 222, 176, 0.3);
">
    <div style="margin-bottom: 8px; font-weight: bold;">🌍 Timezone Debug</div>
    <div id="tzDebugContent" style="line-height: 1.6;"></div>
</div>

<script>
    // Mostrar widget de debug solo si está habilitado en localStorage
    if (localStorage.getItem('tz-debug-enabled')) {
        document.getElementById('tzDebugWidget').style.display = 'block';
    }

    // Función para actualizar el widget
    window.updateTzDebugWidget = function() {
        const widget = document.getElementById('tzDebugWidget');
        const content = document.getElementById('tzDebugContent');
        
        if (!widget.style.display || widget.style.display === 'none') return;

        const deviceTz = Intl.DateTimeFormat().resolvedOptions().timeZone;
        const savedTz = localStorage.getItem('lastSyncedTimezone') || 'ninguno';
        const lastSync = localStorage.getItem('lastSyncTimestamp');
        
        let timeSinceSync = 'N/A';
        if (lastSync) {
            const diff = Math.floor((Date.now() - new Date(lastSync).getTime()) / 1000);
            if (diff < 60) timeSinceSync = `${diff}s atrás`;
            else if (diff < 3600) timeSinceSync = `${Math.floor(diff / 60)}m atrás`;
            else timeSinceSync = `${Math.floor(diff / 3600)}h atrás`;
        }

        content.innerHTML = `
Device: <strong>${deviceTz}</strong><br>
Saved: <strong>${savedTz}</strong><br>
Match: ${deviceTz === savedTz ? '✅' : '❌'}<br>
Last sync: ${timeSinceSync}<br>
<button onclick="window.forceTimezoneSync()" style="
    margin-top: 8px;
    padding: 4px 8px;
    background: #00deb0;
    color: #000;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 11px;
    font-weight: bold;
">FORCE SYNC</button>
        `;
    };

    // Actualizar widget cada 5 segundos
    setInterval(window.updateTzDebugWidget, 5000);
    window.updateTzDebugWidget();

    // Activar debug con comando en consola
    console.log('%c🌍 Timezone Debug', 'color: #00deb0; font-weight: bold; font-size: 14px;');
    console.log('%cRun: localStorage.setItem("tz-debug-enabled", "true"); location.reload();', 'color: #00deb0;');
</script>
