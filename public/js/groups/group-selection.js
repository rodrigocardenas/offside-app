/**
 * Group Selection Module
 * Handles group card interactions and navigation
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeGroupCards();
});

/**
 * Initialize group card interactions
 */
function initializeGroupCards() {
    const groupCards = document.querySelectorAll('.group-card');

    groupCards.forEach(card => {
        // Add hover effects
        card.addEventListener('mouseenter', handleCardHover);
        card.addEventListener('mouseleave', handleCardLeave);

        // Add click feedback
        card.addEventListener('click', handleCardClick);
    });
}

/**
 * Handle card hover
 */
function handleCardHover(e) {
    const card = e.currentTarget;
    if (!card.classList.contains('selected')) {
        card.style.transform = 'translateY(-2px)';
    }
}

/**
 * Handle card leave
 */
function handleCardLeave(e) {
    const card = e.currentTarget;
    if (!card.classList.contains('selected')) {
        card.style.transform = 'translateY(0)';
    }
}

/**
 * Handle card click with visual feedback
 */
function handleCardClick(e) {
    const card = e.currentTarget;

    // Add visual feedback
    card.style.borderColor = '#00857B';

    // Optional: Add loading state
    const originalContent = card.innerHTML;

    // Show loading indicator (optional)
    // card.style.opacity = '0.7';

    // Navigation is handled by the onclick attribute in the blade component
    // This just adds extra visual feedback

    setTimeout(() => {
        card.style.opacity = '1';
    }, 300);
}

/**
 * Select a group programmatically
 * @param {string} groupId - ID of the group to select
 */
function selectGroup(groupId) {
    const cards = document.querySelectorAll('.group-card');

    cards.forEach(card => {
        card.classList.remove('selected');
        card.style.borderColor = '';
    });

    const selectedCard = document.querySelector(`[data-group-id="${groupId}"]`);
    if (selectedCard) {
        selectedCard.classList.add('selected');
        selectedCard.style.borderColor = '#00857B';
    }
}

/**
 * Filter groups by search term
 * @param {string} searchTerm - Term to search for
 */
function filterGroups(searchTerm) {
    const cards = document.querySelectorAll('.group-card');
    const term = searchTerm.toLowerCase();

    cards.forEach(card => {
        const groupName = card.querySelector('h3').textContent.toLowerCase();

        if (groupName.includes(term)) {
            card.style.display = '';
            // Animate in
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 10);
        } else {
            card.style.opacity = '0';
            card.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                card.style.display = 'none';
            }, 300);
        }
    });
}

/**
 * Show group count
 * @returns {number} Number of visible groups
 */
function getVisibleGroupsCount() {
    const cards = document.querySelectorAll('.group-card');
    return Array.from(cards).filter(card => card.style.display !== 'none').length;
}

// Export functions
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        selectGroup,
        filterGroups,
        getVisibleGroupsCount
    };
}
