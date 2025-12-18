/**
 * Notification Checker Module
 * Checks for pending predictions and displays notifications
 */

document.addEventListener('DOMContentLoaded', function() {
    checkPendingPredictions();

    // Check periodically (every 5 minutes)
    setInterval(checkPendingPredictions, 5 * 60 * 1000);
});

/**
 * Check for pending predictions across all groups
 */
function checkPendingPredictions() {
    const groupCards = document.querySelectorAll('.group-card');
    let hasPending = false;

    groupCards.forEach(card => {
        const statusIcon = card.querySelector('.fa-exclamation-triangle');
        if (statusIcon) {
            hasPending = true;
        }
    });

    // Update notification banner
    updateNotificationBanner(hasPending);

    // Update browser tab title if needed
    if (hasPending) {
        updateTabTitle(true);
    }

    return hasPending;
}

/**
 * Update notification banner visibility
 * @param {boolean} show - Whether to show the banner
 */
function updateNotificationBanner(show) {
    const banner = document.getElementById('notification-banner');

    if (banner) {
        if (show) {
            banner.style.display = 'block';
            // Animate in
            setTimeout(() => {
                banner.style.opacity = '1';
            }, 10);
        } else {
            banner.style.opacity = '0';
            setTimeout(() => {
                banner.style.display = 'none';
            }, 300);
        }
    }
}

/**
 * Update browser tab title with notification indicator
 * @param {boolean} hasNotification - Whether there are notifications
 */
function updateTabTitle(hasNotification) {
    const originalTitle = document.title.replace(/^\(\d+\)\s*/, '');

    if (hasNotification) {
        const count = countPendingGroups();
        document.title = `(${count}) ${originalTitle}`;
    } else {
        document.title = originalTitle;
    }
}

/**
 * Count groups with pending predictions
 * @returns {number} Number of groups with pending predictions
 */
function countPendingGroups() {
    const pendingIcons = document.querySelectorAll('.fa-exclamation-triangle');
    return pendingIcons.length;
}

/**
 * Show notification for specific group
 * @param {string} groupName - Name of the group
 * @param {string} message - Notification message
 */
function showGroupNotification(groupName, message) {
    // Check if we have permission for browser notifications
    if ('Notification' in window && Notification.permission === 'granted') {
        new Notification(`${groupName}`, {
            body: message,
            icon: '/favicons/android-chrome-192x192.png',
            badge: '/favicons/android-chrome-192x192.png',
            tag: `group-${groupName}`,
            requireInteraction: false
        });
    }
}

/**
 * Request notification permission
 */
function requestNotificationPermission() {
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission().then(permission => {
            console.log('Notification permission:', permission);
        });
    }
}

/**
 * Check for notifications via API
 * This would connect to a backend endpoint in production
 */
async function checkNotificationsAPI() {
    try {
        const response = await fetch('/api/notifications/pending-predictions', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        });

        if (response.ok) {
            const data = await response.json();

            if (data.hasPending) {
                updateNotificationBanner(true);

                // Show browser notification if enabled
                if (data.groups && data.groups.length > 0) {
                    data.groups.forEach(group => {
                        showGroupNotification(
                            group.name,
                            `Tienes ${group.pending_count} predicción(es) pendiente(s)`
                        );
                    });
                }
            }
        }
    } catch (error) {
        console.error('Error checking notifications:', error);
    }
}

// Export functions
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        checkPendingPredictions,
        updateNotificationBanner,
        countPendingGroups,
        requestNotificationPermission,
        showGroupNotification
    };
}
