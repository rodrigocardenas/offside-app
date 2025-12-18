/**
 * Navigation Module
 * Handles bottom navigation interactions and active state management
 */

document.addEventListener('DOMContentLoaded', function() {
    // Get current page from URL
    const currentPath = window.location.pathname;

    // Map paths to menu items
    const pathMapping = {
        '/groups': 'grupo',
        '/competitions': 'comunidades',
        '/profile': 'perfil'
    };

    // Update active state based on current path
    Object.keys(pathMapping).forEach(path => {
        if (currentPath.includes(path)) {
            const activeItem = pathMapping[path];
            updateActiveMenuItem(activeItem);
        }
    });

    // Add click handlers to menu items
    const menuItems = document.querySelectorAll('.menu-item');
    menuItems.forEach(item => {
        item.addEventListener('click', function(e) {
            // If it's not a button (feedback), let the link navigate normally
            if (this.tagName !== 'BUTTON') {
                // Optional: Add loading state or transition effect
                this.classList.add('opacity-50');
            }
        });
    });
});

/**
 * Update active menu item
 * @param {string} itemName - Name of the menu item to activate
 */
function updateActiveMenuItem(itemName) {
    const menuItems = document.querySelectorAll('.menu-item');
    menuItems.forEach(item => {
        item.classList.remove('active');
    });

    // Find and activate the correct item
    const activeItem = document.querySelector(`.menu-item[data-item="${itemName}"]`);
    if (activeItem) {
        activeItem.classList.add('active');
    }
}

/**
 * Show notification in bottom navigation area
 * @param {string} message - Message to display
 * @param {number} duration - Duration in milliseconds
 */
function showNavigationNotification(message, duration = 3000) {
    const notification = document.createElement('div');
    notification.className = 'fixed bottom-20 left-1/2 -translate-x-1/2 bg-offside-primary text-white px-4 py-2 rounded-lg shadow-lg z-50 text-sm';
    notification.textContent = message;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transition = 'opacity 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, duration);
}

// Export functions for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        updateActiveMenuItem,
        showNavigationNotification
    };
}
