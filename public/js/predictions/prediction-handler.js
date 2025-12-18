/**
 * Prediction Handler Module
 * Handles prediction selection and submission
 */

/**
 * Select a prediction option
 * @param {HTMLElement} button - The clicked button
 * @param {number} questionId - ID of the question
 * @param {number} optionId - ID of the selected option
 */
function selectPredictionOption(button, questionId, optionId) {
    // Don't allow selection if disabled
    if (button.disabled) {
        return;
    }

    // Show loading state
    button.disabled = true;
    button.innerHTML += ' <i class="fas fa-spinner fa-spin ml-2"></i>';

    // Send answer to server
    submitPrediction(questionId, optionId)
        .then(response => {
            if (response.success) {
                // Mark as selected
                markOptionAsSelected(questionId, optionId);

                // Show success feedback
                showPredictionFeedback(questionId, 'success', response.message || '¡Predicción guardada!');

                // Update points if provided
                if (response.points) {
                    updateUserPoints(response.points);
                }
            } else {
                // Show error
                showPredictionFeedback(questionId, 'error', response.message || 'Error al guardar predicción');

                // Re-enable button
                button.disabled = false;
                removeSpinner(button);
            }
        })
        .catch(error => {
            console.error('Error submitting prediction:', error);
            showPredictionFeedback(questionId, 'error', 'Error de conexión. Intenta de nuevo.');

            // Re-enable button
            button.disabled = false;
            removeSpinner(button);
        });
}

/**
 * Submit prediction to server
 * @param {number} questionId - ID of the question
 * @param {number} optionId - ID of the selected option
 * @returns {Promise} Response promise
 */
async function submitPrediction(questionId, optionId) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    if (!csrfToken) {
        console.error('CSRF token not found');
        return { success: false, message: 'Error de seguridad' };
    }

    try {
        const response = await fetch(`/questions/${questionId}/answer`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                question_option_id: optionId
            })
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Error en la respuesta del servidor');
        }

        return await response.json();
    } catch (error) {
        console.error('Fetch error:', error);
        throw error;
    }
}

/**
 * Mark option as selected in UI
 * @param {number} questionId - ID of the question
 * @param {number} optionId - ID of the selected option
 */
function markOptionAsSelected(questionId, optionId) {
    const container = document.getElementById(`options-${questionId}`);

    if (!container) return;

    // Remove selected class from all options
    const allButtons = container.querySelectorAll('.option-btn');
    allButtons.forEach(btn => {
        btn.classList.remove('selected');
        btn.disabled = true; // Disable all after selection
        removeSpinner(btn);
    });

    // Add selected class to chosen option
    const selectedButton = container.querySelector(`[data-option-id="${optionId}"]`);
    if (selectedButton) {
        selectedButton.classList.add('selected');

        // Add check icon if not already present
        if (!selectedButton.querySelector('.fa-check-circle')) {
            selectedButton.innerHTML += ' <i class="fas fa-check-circle ml-2"></i>';
        }
    }
}

/**
 * Show feedback message
 * @param {number} questionId - ID of the question
 * @param {string} type - Type of feedback ('success' or 'error')
 * @param {string} message - Message to display
 */
function showPredictionFeedback(questionId, type, message) {
    const feedbackDiv = document.getElementById(`prediction-feedback-${questionId}`);

    if (!feedbackDiv) return;

    // Update content
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
    const bgColor = type === 'success' ? 'bg-offside-primary' : 'bg-red-500';

    feedbackDiv.innerHTML = `
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-lg ${bgColor} text-white text-sm font-semibold">
            <i class="fas ${icon}"></i>
            <span>${message}</span>
        </div>
    `;

    // Show with animation
    feedbackDiv.classList.remove('hidden');
    feedbackDiv.style.opacity = '0';
    setTimeout(() => {
        feedbackDiv.style.opacity = '1';
        feedbackDiv.style.transition = 'opacity 0.3s ease';
    }, 10);

    // Hide after 3 seconds
    setTimeout(() => {
        feedbackDiv.style.opacity = '0';
        setTimeout(() => {
            feedbackDiv.classList.add('hidden');
        }, 300);
    }, 3000);
}

/**
 * Remove spinner from button
 * @param {HTMLElement} button - Button element
 */
function removeSpinner(button) {
    const spinner = button.querySelector('.fa-spinner');
    if (spinner) {
        spinner.remove();
    }
}

/**
 * Update user points in UI
 * @param {number} points - New points total
 */
function updateUserPoints(points) {
    // Update points display if exists
    const pointsDisplay = document.querySelector('[data-user-points]');
    if (pointsDisplay) {
        pointsDisplay.textContent = points;

        // Add pulse animation
        if (typeof pulseElement === 'function') {
            pulseElement(pointsDisplay);
        }
    }
}

/**
 * Check if all predictions are completed
 */
function checkAllPredictionsCompleted() {
    const allPredictionSections = document.querySelectorAll('.prediction-section');
    let allCompleted = true;

    allPredictionSections.forEach(section => {
        const selectedOption = section.querySelector('.option-btn.selected');
        if (!selectedOption) {
            allCompleted = false;
        }
    });

    if (allCompleted) {
        showCompletionMessage();
    }
}

/**
 * Show completion message when all predictions are done
 */
function showCompletionMessage() {
    if (typeof showNavigationNotification === 'function') {
        showNavigationNotification('¡Has completado todas las predicciones! 🎉', 4000);
    }
}

// Export functions
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        selectPredictionOption,
        submitPrediction,
        markOptionAsSelected,
        showPredictionFeedback
    };
}
