/**
 * Permission Service - Capacitor 6 (Android 13+ POST_NOTIFICATIONS)
 * 
 * PURPOSE: Centralized permission management for push notifications
 * - Handles Android 13+ POST_NOTIFICATIONS permission
 * - Provides retry logic for permission requests
 * - Integrates with NativeFirebaseMessagingService
 * - Respects user choice (no spam)
 * 
 * Author: Senior Hybrid Architecture Expert
 * Date: February 2026
 * Version: 1.0
 */

class PermissionService {
    constructor() {
        this.maxRetries = 2;
        this.retryDelayMs = 3000; // 3 seconds between retries
        this.permissionDeniedCount = 0;
        this.lastDenialTime = null;
        this.cooldownMs = 60000; // 1 minute cooldown after denial
        
        // Storage keys
        this.STORAGE_KEY_DENIAL_COUNT = 'fcm_permission_denial_count';
        this.STORAGE_KEY_LAST_DENIAL = 'fcm_permission_last_denial';
        
        // Load saved state
        this.loadState();
        
        console.log('[PermissionService] Initialized', this.getState());
    }

    /**
     * Load persisted state from localStorage
     */
    loadState() {
        try {
            const denialCount = localStorage.getItem(this.STORAGE_KEY_DENIAL_COUNT);
            const lastDenial = localStorage.getItem(this.STORAGE_KEY_LAST_DENIAL);
            
            this.permissionDeniedCount = denialCount ? parseInt(denialCount) : 0;
            this.lastDenialTime = lastDenial ? new Date(lastDenial) : null;
        } catch (error) {
            console.warn('[PermissionService] Could not load state from localStorage:', error);
        }
    }

    /**
     * Persist state to localStorage
     */
    saveState() {
        try {
            localStorage.setItem(this.STORAGE_KEY_DENIAL_COUNT, this.permissionDeniedCount.toString());
            if (this.lastDenialTime) {
                localStorage.setItem(this.STORAGE_KEY_LAST_DENIAL, this.lastDenialTime.toISOString());
            }
        } catch (error) {
            console.warn('[PermissionService] Could not save state to localStorage:', error);
        }
    }

    /**
     * Get service state for debugging
     */
    getState() {
        return {
            permissionDeniedCount: this.permissionDeniedCount,
            lastDenialTime: this.lastDenialTime?.toISOString() || null,
            isInCooldown: this.isInCooldown(),
            maxRetries: this.maxRetries
        };
    }

    /**
     * Check if currently in cooldown after denial
     */
    isInCooldown() {
        if (!this.lastDenialTime) return false;
        const elapsed = Date.now() - this.lastDenialTime.getTime();
        return elapsed < this.cooldownMs;
    }

    /**
     * Get time remaining in cooldown (ms)
     */
    getCooldownTimeRemaining() {
        if (!this.isInCooldown()) return 0;
        const elapsed = Date.now() - this.lastDenialTime.getTime();
        return Math.max(0, this.cooldownMs - elapsed);
    }

    /**
     * Main permission request method
     * Handles retries and tracks denials
     */
    async requestNotificationPermission() {
        try {
            // Check if in cooldown
            if (this.isInCooldown()) {
                const remaining = this.getCooldownTimeRemaining();
                const secondsRemaining = Math.ceil(remaining / 1000);
                
                console.log(`[PermissionService] ⏳ In cooldown. Try again in ${secondsRemaining}s`);
                return {
                    success: false,
                    error: 'COOLDOWN',
                    message: `Please wait ${secondsRemaining}s before trying again`,
                    data: { secondsRemaining }
                };
            }

            // Check if max retries exceeded
            if (this.permissionDeniedCount >= this.maxRetries) {
                console.log(`[PermissionService] ❌ Max retries (${this.maxRetries}) exceeded`);
                return {
                    success: false,
                    error: 'MAX_RETRIES_EXCEEDED',
                    message: 'Maximum retry attempts exceeded. Please enable notifications manually in settings.',
                    data: {
                        denialCount: this.permissionDeniedCount,
                        maxRetries: this.maxRetries,
                        helpUrl: 'app-settings://notification/com.offsideclub.app'
                    }
                };
            }

            // Get plugin reference
            const plugin = this.getFirebaseMessagingPlugin();
            if (!plugin) {
                console.error('[PermissionService] Firebase Messaging plugin not available');
                return {
                    success: false,
                    error: 'PLUGIN_UNAVAILABLE',
                    message: 'Firebase Messaging plugin not found'
                };
            }

            // Request permission
            console.log(`[PermissionService] 📲 Requesting permission (attempt ${this.permissionDeniedCount + 1})`);
            const result = await plugin.requestPermissions();
            
            // Check result
            if (result?.display === 'granted') {
                console.log('[PermissionService] ✅ Permission GRANTED');
                this.permissionDeniedCount = 0; // Reset counter on success
                this.saveState();
                
                return {
                    success: true,
                    message: 'Notification permission granted',
                    data: result
                };
            } else {
                console.log('[PermissionService] ⚠️  Permission DENIED or PROMPT_DISMISSED');
                this.permissionDeniedCount++;
                this.lastDenialTime = new Date();
                this.saveState();
                
                return {
                    success: false,
                    error: 'PERMISSION_DENIED',
                    message: 'User denied notification permission',
                    data: {
                        display: result?.display || 'denied',
                        denialCount: this.permissionDeniedCount,
                        maxRetries: this.maxRetries,
                        canRetry: this.permissionDeniedCount < this.maxRetries
                    }
                };
            }

        } catch (error) {
            console.error('[PermissionService] Error requesting permission:', error);
            return {
                success: false,
                error: 'PERMISSION_REQUEST_ERROR',
                message: error.message,
                data: { originalError: error }
            };
        }
    }

    /**
     * Check current permission status
     */
    async checkPermissionStatus() {
        try {
            const plugin = this.getFirebaseMessagingPlugin();
            if (!plugin) {
                console.error('[PermissionService] Plugin not available for status check');
                return { display: 'denied' };
            }

            const result = await plugin.checkPermissions();
            console.log('[PermissionService] Permission status:', result?.display || 'unknown');
            return result;

        } catch (error) {
            console.error('[PermissionService] Error checking permission status:', error);
            return { display: 'denied' };
        }
    }

    /**
     * Soft check: return current status without requesting
     * Returns: 'granted' | 'denied' | 'prompt_once' | 'unknown'
     */
    async getSoftPermissionStatus() {
        return this.checkPermissionStatus();
    }

    /**
     * Reset permission tracking
     * Useful for testing or manual reset
     */
    resetPermissionTracking() {
        this.permissionDeniedCount = 0;
        this.lastDenialTime = null;
        localStorage.removeItem(this.STORAGE_KEY_DENIAL_COUNT);
        localStorage.removeItem(this.STORAGE_KEY_LAST_DENIAL);
        console.log('[PermissionService] Permission tracking reset');
    }

    /**
     * Get Firebase Messaging plugin
     */
    getFirebaseMessagingPlugin() {
        // Use the same detection logic as NativeFirebaseMessagingService
        if (window.Capacitor?.Plugins?.FirebaseMessaging) {
            return window.Capacitor.Plugins.FirebaseMessaging;
        }
        if (window.plugins?.FirebaseMessaging) {
            return window.plugins.FirebaseMessaging;
        }
        if (window.FirebaseMessaging) {
            return window.FirebaseMessaging;
        }
        return null;
    }

    /**
     * Initialize with custom options
     */
    configure(options = {}) {
        if (options.maxRetries !== undefined) {
            this.maxRetries = options.maxRetries;
            console.log(`[PermissionService] maxRetries set to ${this.maxRetries}`);
        }
        if (options.retryDelayMs !== undefined) {
            this.retryDelayMs = options.retryDelayMs;
            console.log(`[PermissionService] retryDelayMs set to ${this.retryDelayMs}`);
        }
        if (options.cooldownMs !== undefined) {
            this.cooldownMs = options.cooldownMs;
            console.log(`[PermissionService] cooldownMs set to ${this.cooldownMs}`);
        }
    }

    /**
     * Get user-friendly message for UI display
     */
    getHumanReadableMessage(result) {
        if (!result) return '';

        switch (result.error) {
            case 'COOLDOWN':
                return `Please wait before trying again`;
            
            case 'MAX_RETRIES_EXCEEDED':
                return 'You can enable notifications in app settings';
            
            case 'PERMISSION_DENIED':
                if (result.data?.canRetry) {
                    return `Notifications disabled. Enable in settings or try again (${this.maxRetries - result.data.denialCount} attempts remaining)`;
                } else {
                    return 'To enable notifications, go to Settings > Notifications';
                }
            
            case 'PLUGIN_UNAVAILABLE':
                return 'Notification system unavailable';
            
            case 'PERMISSION_REQUEST_ERROR':
                return 'Error requesting permission. Please try again.';
            
            default:
                return result.message || 'Unknown error';
        }
    }
}

// ============================================
// GLOBAL INITIALIZATION
// ============================================

// Expose service to window
window.PermissionService = null;

/**
 * Initialize permission service on DOM ready
 */
function initializePermissionService() {
    window.PermissionService = new PermissionService();
    
    // Global API
    window.requestNotificationPermission = async function() {
        if (!window.PermissionService) {
            console.error('[PermissionService] Service not initialized');
            return { success: false, error: 'Service not initialized' };
        }
        return window.PermissionService.requestNotificationPermission();
    };

    /**
     * Check permission status only (no request)
     */
    window.checkNotificationPermission = async function() {
        if (!window.PermissionService) {
            return { display: 'denied' };
        }
        return window.PermissionService.checkPermissionStatus();
    };

    /**
     * Get permission service state
     */
    window.getPermissionServiceState = function() {
        if (!window.PermissionService) {
            return null;
        }
        return window.PermissionService.getState();
    };

    /**
     * Reset permission tracking
     */
    window.resetPermissionTracking = function() {
        if (!window.PermissionService) {
            return;
        }
        window.PermissionService.resetPermissionTracking();
    };

    console.log('✅ [PermissionService] Script loaded successfully');
    console.log('   Available functions:');
    console.log('   - window.requestNotificationPermission()');
    console.log('   - window.checkNotificationPermission()');
    console.log('   - window.getPermissionServiceState()');
    console.log('   - window.resetPermissionTracking()');
}

// Listen for DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializePermissionService);
} else {
    initializePermissionService();
}
