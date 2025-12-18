/**
 * Chat Handler Module
 * Handles chat message sending and receiving
 */

/**
 * Send chat message
 * @param {Event} event - Form submit event
 * @param {number} groupId - ID of the group
 */
function sendChatMessage(event, groupId) {
    event.preventDefault();

    const form = document.getElementById(`chat-form-${groupId}`);
    const input = document.getElementById(`chat-input-${groupId}`);
    const sendBtn = document.getElementById(`chat-send-btn-${groupId}`);
    const messageText = input.value.trim();

    if (!messageText) {
        return;
    }

    // Disable input and button
    input.disabled = true;
    sendBtn.disabled = true;
    sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    // Send message
    submitChatMessage(groupId, messageText)
        .then(response => {
            if (response.success) {
                // Clear input
                input.value = '';

                // Reset character counter
                const counter = document.getElementById(`char-counter-${groupId}`);
                if (counter) {
                    counter.textContent = '0/500';
                }

                // Add message to chat (if not using Pusher)
                if (!window.Echo) {
                    addMessageToChat(groupId, response.message);
                }

                // Re-enable input
                input.disabled = false;
                sendBtn.disabled = false;
                sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
                input.focus();
            } else {
                showChatError(groupId, response.message || 'Error al enviar mensaje');
                input.disabled = false;
                sendBtn.disabled = false;
                sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
            }
        })
        .catch(error => {
            console.error('Error sending message:', error);
            showChatError(groupId, 'Error de conexión. Intenta de nuevo.');
            input.disabled = false;
            sendBtn.disabled = false;
            sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
        });
}

/**
 * Submit chat message to server
 * @param {number} groupId - ID of the group
 * @param {string} message - Message text
 * @returns {Promise} Response promise
 */
async function submitChatMessage(groupId, message) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    if (!csrfToken) {
        console.error('CSRF token not found');
        return { success: false, message: 'Error de seguridad' };
    }

    try {
        const response = await fetch(`/groups/${groupId}/chat`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                message: message
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
 * Add message to chat UI
 * @param {number} groupId - ID of the group
 * @param {Object} message - Message object
 */
function addMessageToChat(groupId, message) {
    const chatContainer = document.getElementById(`chat-messages-${groupId}`);

    if (!chatContainer) return;

    // Check if empty state message exists
    const emptyState = chatContainer.querySelector('.text-center.py-8');
    if (emptyState) {
        emptyState.remove();
    }

    // Create message element
    const messageElement = createMessageElement(message, true);

    // Add to chat
    chatContainer.appendChild(messageElement);

    // Scroll to bottom
    scrollToBottom(groupId);

    // Add entrance animation
    setTimeout(() => {
        messageElement.style.opacity = '1';
        messageElement.style.transform = 'translateY(0)';
    }, 10);
}

/**
 * Create message element
 * @param {Object} message - Message object
 * @param {boolean} isCurrentUser - If message is from current user
 * @returns {HTMLElement} Message element
 */
function createMessageElement(message, isCurrentUser) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `flex gap-3 items-start ${isCurrentUser ? 'flex-row-reverse' : ''}`;
    messageDiv.style.opacity = '0';
    messageDiv.style.transform = 'translateY(10px)';
    messageDiv.style.transition = 'all 0.3s ease';

    const initials = message.user?.name ? message.user.name.substring(0, 2).toUpperCase() : 'TU';
    const userName = isCurrentUser ? 'Tú' : (message.user?.name || 'Usuario');
    const bgColor = isCurrentUser ? 'bg-offside-secondary' : 'bg-offside-primary';
    const msgBgColor = isCurrentUser ? 'bg-offside-primary text-white rounded-tr-none' : 'bg-gray-100 text-gray-800 rounded-tl-none';
    const userColor = isCurrentUser ? 'text-offside-primary' : '';

    messageDiv.innerHTML = `
        <div class="message-avatar flex-shrink-0 ${bgColor}">
            ${initials}
        </div>
        <div class="flex-1 min-w-0 ${isCurrentUser ? 'items-end' : 'items-start'} flex flex-col">
            <div class="flex items-center gap-2 mb-1 ${isCurrentUser ? 'flex-row-reverse' : ''}">
                <span class="text-xs font-semibold text-gray-800 ${userColor}">
                    ${userName}
                </span>
                <span class="text-[10px] text-gray-500">
                    ahora
                </span>
            </div>
            <div class="max-w-[85%] ${isCurrentUser ? 'ml-auto' : 'mr-auto'}">
                <div class="px-3 py-2 rounded-lg text-sm leading-relaxed ${msgBgColor}">
                    ${escapeHtml(message.message)}
                </div>
            </div>
        </div>
    `;

    return messageDiv;
}

/**
 * Show chat error
 * @param {number} groupId - ID of the group
 * @param {string} message - Error message
 */
function showChatError(groupId, message) {
    const chatContainer = document.getElementById(`chat-messages-${groupId}`);

    if (!chatContainer) return;

    const errorDiv = document.createElement('div');
    errorDiv.className = 'bg-red-100 border border-red-300 text-red-700 px-3 py-2 rounded-lg text-xs text-center';
    errorDiv.textContent = message;

    chatContainer.appendChild(errorDiv);

    // Remove after 3 seconds
    setTimeout(() => {
        errorDiv.style.opacity = '0';
        errorDiv.style.transition = 'opacity 0.3s ease';
        setTimeout(() => errorDiv.remove(), 300);
    }, 3000);

    scrollToBottom(groupId);
}

/**
 * Scroll chat to bottom
 * @param {number} groupId - ID of the group
 */
function scrollToBottom(groupId) {
    const chatContainer = document.getElementById(`chat-messages-${groupId}`);

    if (!chatContainer) return;

    chatContainer.scrollTo({
        top: chatContainer.scrollHeight,
        behavior: 'smooth'
    });
}

/**
 * Escape HTML to prevent XSS
 * @param {string} text - Text to escape
 * @returns {string} Escaped text
 */
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

/**
 * Handle incoming message from Pusher
 * @param {Object} data - Message data from Pusher
 */
function handleIncomingMessage(data) {
    const groupId = data.group_id;
    const currentUserId = document.querySelector('meta[name="user-id"]')?.content;
    const isCurrentUser = data.user_id == currentUserId;

    // Don't add if it's from current user (already added optimistically)
    if (!isCurrentUser) {
        addMessageToChat(groupId, data);

        // Play sound notification (optional)
        playNotificationSound();
    }
}

/**
 * Play notification sound
 */
function playNotificationSound() {
    // Optional: Add sound notification
    if (typeof Audio !== 'undefined') {
        try {
            const audio = new Audio('/sounds/notification.mp3');
            audio.volume = 0.3;
            audio.play().catch(e => console.log('Could not play sound:', e));
        } catch (e) {
            console.log('Audio not supported');
        }
    }
}

/**
 * Mark messages as read
 * @param {number} groupId - ID of the group
 */
async function markMessagesAsRead(groupId) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    try {
        await fetch(`/groups/${groupId}/chat/mark-as-read`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        });
    } catch (error) {
        console.error('Error marking messages as read:', error);
    }
}

/**
 * Load more messages (pagination)
 * @param {number} groupId - ID of the group
 * @param {number} page - Page number
 */
async function loadMoreMessages(groupId, page = 1) {
    try {
        const response = await fetch(`/groups/${groupId}/chat?page=${page}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error('Failed to load messages');
        }

        const data = await response.json();

        // Prepend messages to chat
        prependMessages(groupId, data.messages);

        return data;
    } catch (error) {
        console.error('Error loading more messages:', error);
        return null;
    }
}

/**
 * Prepend messages to chat
 * @param {number} groupId - ID of the group
 * @param {Array} messages - Array of messages
 */
function prependMessages(groupId, messages) {
    const chatContainer = document.getElementById(`chat-messages-${groupId}`);

    if (!chatContainer || !messages || messages.length === 0) return;

    const currentScrollHeight = chatContainer.scrollHeight;

    messages.reverse().forEach(message => {
        const messageElement = createMessageElement(message, message.is_current_user);
        messageElement.style.opacity = '1';
        messageElement.style.transform = 'translateY(0)';
        chatContainer.insertBefore(messageElement, chatContainer.firstChild);
    });

    // Maintain scroll position
    chatContainer.scrollTop = chatContainer.scrollHeight - currentScrollHeight;
}

// Auto-mark as read when scrolling to bottom
document.addEventListener('DOMContentLoaded', function() {
    const chatContainers = document.querySelectorAll('[id^="chat-messages-"]');

    chatContainers.forEach(container => {
        const groupId = container.getAttribute('data-group-id');

        container.addEventListener('scroll', function() {
            const isAtBottom = this.scrollHeight - this.scrollTop <= this.clientHeight + 50;

            if (isAtBottom) {
                markMessagesAsRead(groupId);
            }
        });

        // Mark as read on load if at bottom
        const isAtBottom = container.scrollHeight - container.scrollTop <= container.clientHeight + 50;
        if (isAtBottom) {
            markMessagesAsRead(groupId);
        }
    });
});

// Export functions
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        sendChatMessage,
        submitChatMessage,
        addMessageToChat,
        handleIncomingMessage,
        markMessagesAsRead,
        scrollToBottom
    };
}
