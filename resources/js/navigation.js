// Toggle the dropdown menu
const userMenuButton = document.getElementById('user-menu');
const userMenu = document.querySelector('[aria-labelledby="user-menu"]');

if (userMenuButton && userMenu) {
    userMenuButton.addEventListener('click', () => {
        const isExpanded = userMenuButton.getAttribute('aria-expanded') === 'true';
        userMenuButton.setAttribute('aria-expanded', !isExpanded);
        userMenu.classList.toggle('hidden');
    });

    // Close the dropdown when clicking outside
    document.addEventListener('click', (event) => {
        const isClickInside = userMenuButton.contains(event.target) || userMenu.contains(event.target);
        if (!isClickInside) {
            userMenuButton.setAttribute('aria-expanded', 'false');
            userMenu.classList.add('hidden');
        }
    });
}
