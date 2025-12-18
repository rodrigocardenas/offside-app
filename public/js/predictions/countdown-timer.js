/**
 * Countdown Timer Module
 * Handles countdown timers for predictions
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeCountdownTimers();

    // Update every second
    setInterval(updateAllTimers, 1000);
});

/**
 * Initialize all countdown timers on the page
 */
function initializeCountdownTimers() {
    const timers = document.querySelectorAll('[id^="prediction-timer-"]');

    timers.forEach(timer => {
        const endTime = timer.getAttribute('data-end-time');
        if (endTime) {
            updateTimer(timer, endTime);
        }
    });
}

/**
 * Update all timers
 */
function updateAllTimers() {
    const timers = document.querySelectorAll('[id^="prediction-timer-"]');

    timers.forEach(timer => {
        const endTime = timer.getAttribute('data-end-time');
        if (endTime) {
            updateTimer(timer, endTime);
        }
    });
}

/**
 * Update a single timer
 * @param {HTMLElement} timerElement - Timer element
 * @param {string} endTime - End time in ISO format
 */
function updateTimer(timerElement, endTime) {
    const textSpan = timerElement.querySelector('.timer-text');

    if (!textSpan) return;

    const timeLeft = calculateTimeLeft(endTime);

    if (timeLeft.expired) {
        textSpan.textContent = 'Predicción cerrada';
        timerElement.classList.add('text-red-500');

        // Disable all options
        const questionId = timerElement.id.replace('prediction-timer-', '');
        disableQuestionOptions(questionId);
    } else {
        const formattedTime = formatTimeLeft(timeLeft);
        textSpan.textContent = `Quedan ${formattedTime} para cerrar predicciones`;

        // Add warning color if less than 1 hour
        if (timeLeft.totalMinutes < 60) {
            timerElement.classList.add('text-yellow-600');
        }
    }
}

/**
 * Calculate time left until end time
 * @param {string} endTime - End time in ISO format
 * @returns {Object} Time left object
 */
function calculateTimeLeft(endTime) {
    const now = new Date();
    const end = new Date(endTime);
    const diff = end - now;

    if (diff <= 0) {
        return { expired: true };
    }

    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((diff % (1000 * 60)) / 1000);
    const totalMinutes = Math.floor(diff / (1000 * 60));

    return {
        expired: false,
        days,
        hours,
        minutes,
        seconds,
        totalMinutes
    };
}

/**
 * Format time left into readable string
 * @param {Object} timeLeft - Time left object
 * @returns {string} Formatted time string
 */
function formatTimeLeft(timeLeft) {
    if (timeLeft.days > 0) {
        return `${timeLeft.days}d ${timeLeft.hours}h`;
    } else if (timeLeft.hours > 0) {
        return `${timeLeft.hours}h ${timeLeft.minutes}m`;
    } else if (timeLeft.minutes > 0) {
        return `${timeLeft.minutes}m ${timeLeft.seconds}s`;
    } else {
        return `${timeLeft.seconds}s`;
    }
}

/**
 * Disable all options for a question
 * @param {number} questionId - ID of the question
 */
function disableQuestionOptions(questionId) {
    const container = document.getElementById(`options-${questionId}`);

    if (!container) return;

    const buttons = container.querySelectorAll('.option-btn');
    buttons.forEach(btn => {
        if (!btn.classList.contains('selected')) {
            btn.disabled = true;
            btn.classList.add('opacity-50', 'cursor-not-allowed');
        }
    });
}

/**
 * Create a countdown timer element programmatically
 * @param {number} questionId - ID of the question
 * @param {string} endTime - End time in ISO format
 * @returns {HTMLElement} Timer element
 */
function createCountdownTimer(questionId, endTime) {
    const timer = document.createElement('div');
    timer.className = 'timer';
    timer.id = `prediction-timer-${questionId}`;
    timer.setAttribute('data-end-time', endTime);
    timer.innerHTML = `
        <i class="fas fa-clock"></i>
        <span class="timer-text">Calculando...</span>
    `;

    return timer;
}

/**
 * Show alert when time is running out
 * @param {number} minutes - Minutes threshold
 */
function setupTimeWarning(minutes = 5) {
    const timers = document.querySelectorAll('[id^="prediction-timer-"]');

    timers.forEach(timer => {
        const endTime = timer.getAttribute('data-end-time');
        const timeLeft = calculateTimeLeft(endTime);

        if (!timeLeft.expired && timeLeft.totalMinutes === minutes) {
            showTimeWarning(minutes);
        }
    });
}

/**
 * Show time warning notification
 * @param {number} minutes - Minutes left
 */
function showTimeWarning(minutes) {
    if (typeof showNavigationNotification === 'function') {
        showNavigationNotification(
            `⏰ ¡Solo quedan ${minutes} minutos para cerrar las predicciones!`,
            5000
        );
    }
}

// Check for warnings every minute
setInterval(() => {
    setupTimeWarning(5);
    setupTimeWarning(1);
}, 60000);

// Export functions
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        initializeCountdownTimers,
        updateTimer,
        calculateTimeLeft,
        formatTimeLeft,
        createCountdownTimer
    };
}
