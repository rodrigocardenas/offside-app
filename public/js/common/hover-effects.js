/**
 * Hover Effects Module
 * Global hover effects for interactive elements
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeHoverEffects();
});

/**
 * Initialize hover effects
 */
function initializeHoverEffects() {
    // Cards hover effects
    initializeCardHovers();

    // Button hover effects
    initializeButtonHovers();

    // Icon hover effects
    initializeIconHovers();
}

/**
 * Initialize card hover effects
 */
function initializeCardHovers() {
    const cards = document.querySelectorAll('.group-card, .player-item, .match-card');

    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            if (!this.classList.contains('no-hover')) {
                this.style.transform = 'translateY(-2px)';
                this.style.transition = 'all 0.2s ease';
            }
        });

        card.addEventListener('mouseleave', function() {
            if (!this.classList.contains('no-hover')) {
                this.style.transform = 'translateY(0)';
            }
        });
    });
}

/**
 * Initialize button hover effects
 */
function initializeButtonHovers() {
    const buttons = document.querySelectorAll('.option-btn:not(.selected)');

    buttons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.transition = 'all 0.3s ease';
        });

        button.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
}

/**
 * Initialize icon hover effects
 */
function initializeIconHovers() {
    const icons = document.querySelectorAll('.hover-icon');

    icons.forEach(icon => {
        icon.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.1)';
            this.style.transition = 'transform 0.2s ease';
        });

        icon.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
}

/**
 * Add ripple effect to element
 * @param {HTMLElement} element - Element to add ripple to
 * @param {Event} event - Click event
 */
function addRippleEffect(element, event) {
    const ripple = document.createElement('span');
    const rect = element.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    const x = event.clientX - rect.left - size / 2;
    const y = event.clientY - rect.top - size / 2;

    ripple.style.width = ripple.style.height = size + 'px';
    ripple.style.left = x + 'px';
    ripple.style.top = y + 'px';
    ripple.classList.add('ripple');

    element.appendChild(ripple);

    setTimeout(() => {
        ripple.remove();
    }, 600);
}

/**
 * Add pulse animation to element
 * @param {HTMLElement} element - Element to pulse
 */
function pulseElement(element) {
    element.style.animation = 'pulse 0.5s ease';

    setTimeout(() => {
        element.style.animation = '';
    }, 500);
}

/**
 * Shake element (for errors)
 * @param {HTMLElement} element - Element to shake
 */
function shakeElement(element) {
    element.style.animation = 'shake 0.5s ease';

    setTimeout(() => {
        element.style.animation = '';
    }, 500);
}

// CSS animations (add to your CSS file or inject dynamically)
const style = document.createElement('style');
style.textContent = `
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }

    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-10px); }
        75% { transform: translateX(10px); }
    }

    .ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(0, 133, 123, 0.3);
        transform: scale(0);
        animation: ripple-animation 0.6s ease-out;
        pointer-events: none;
    }

    @keyframes ripple-animation {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Export functions
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        addRippleEffect,
        pulseElement,
        shakeElement,
        initializeHoverEffects
    };
}
