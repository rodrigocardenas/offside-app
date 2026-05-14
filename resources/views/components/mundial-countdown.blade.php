@php
    $themeMode = auth()->user()->theme_mode ?? 'light';
    $isDark = $themeMode === 'dark';
    $stripBg   = $isDark ? 'rgba(201, 168, 76, 0.10)' : 'rgba(201, 168, 76, 0.25)';
    $textColor = $isDark ? '#E8C96A' : '#8B6914';
    $dotColor  = $isDark ? '#C9A84C' : '#C9A84C';
@endphp

<marquee id="mundial-countdown-strip"
     style="background: {{ $stripBg }}; border-bottom: 1px solid rgba(201,168,76,0.20); padding: 1px 16px; display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 11px; font-weight: 600; color: {{ $textColor }}; letter-spacing: 0.03em; user-select: none;">
    <span></span>
    <span style="color: {{ $dotColor }}; opacity: 0.5;">·</span>
    <span id="mundial-days-label">cargando...</span>
</marquee>

<script>
(function () {
    var strip = document.getElementById('mundial-countdown-strip');
    var label = document.getElementById('mundial-days-label');
    if (!strip || !label) return;

    var kickoff = new Date('2026-06-11T00:00:00');
    var now = new Date();
    var diff = kickoff - now;

    if (diff <= 0) {
        // El Mundial ya empezó — ocultar la tira
        strip.style.display = 'none';
        return;
    }

    var days  = Math.floor(diff / (1000 * 60 * 60 * 24));
    var hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));

    if (days === 0) {
        label.textContent = '¡Hoy empieza! 🏆';
    } else if (days === 1) {
        label.textContent = '¡Mañana comienza!';
    } else {
        label.textContent = 'Solo ' + days + ' días para que comience el Mundial!';
    }
})();
</script>
