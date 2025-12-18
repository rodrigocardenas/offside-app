/**
 * Header Profile Dropdown Handler
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Inicializando header dropdown handler');

    const profileBtn = document.querySelector('.profile-btn');
    const profileDropdown = document.querySelector('.profile-dropdown');

    if (!profileBtn || !profileDropdown) {
        console.warn('⚠️ No se encontraron elementos del dropdown');
        return;
    }

    console.log('✅ Elementos del dropdown encontrados');

    // Toggle dropdown al hacer click en el botón
    profileBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        console.log('🖱️ Click en profile btn');
        profileDropdown.classList.toggle('active');
        const isActive = profileDropdown.classList.contains('active');
        console.log('📌 Dropdown visibility:', isActive ? 'visible' : 'hidden');
    });

    // Cerrar dropdown al hacer click fuera
    document.addEventListener('click', function(e) {
        if (!profileBtn.contains(e.target) && !profileDropdown.contains(e.target)) {
            profileDropdown.classList.remove('active');
        }
    });

    // Cerrar dropdown al hacer click dentro (para navegar)
    profileDropdown.addEventListener('click', function(e) {
        if (e.target.closest('a')) {
            profileDropdown.classList.remove('active');
        }
    });

    console.log('✅ Header dropdown handler inicializado correctamente');
});
