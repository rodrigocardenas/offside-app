/**
 * Token Service - FCM Token Management
 * 
 * PURPOSE: Centralized FCM token management
 * - Obtains and stores FCM token
 * - Handles token refresh/rotation
 * - Syncs with backend
 * - Detects stale tokens
 * 
 * Author: Senior Hybrid Architecture Expert
 * Date: February 2026
 * Version: 1.0
 */

class TokenService {
    constructor() {
        this.currentToken = null;
        this.obtainedAt = null;
        this.lastSyncedAt = null;
        this.syncedWithBackend = false;
        this.refreshCheckInterval = null;
        this.autoRefreshEnabled = true;
        
        // Token validation thresholds
        this.TOKEN_VALIDITY_MS = 30 * 24 * 60 * 60 * 1000; // 30 days
        this.REFRESH_CHECK_INTERVAL_MS = 60 * 60 * 1000; // Check every hour
        
        // Storage keys
        this.STORAGE_KEY_TOKEN = 'fcm_device_token';
        this.STORAGE_KEY_OBTAINED_AT = 'fcm_token_obtained_at';
        this.STORAGE_KEY_SYNCED_AT = 'fcm_token_synced_at';
        
        // Load saved token
        this.loadSavedToken();
        
        console.log('[TokenService] Initialized', this.getState());
    }

    /**
     * Load token from localStorage
     */
    loadSavedToken() {
        try {
            const token = localStorage.getItem(this.STORAGE_KEY_TOKEN);
            const obtainedAt = localStorage.getItem(this.STORAGE_KEY_OBTAINED_AT);
            const syncedAt = localStorage.getItem(this.STORAGE_KEY_SYNCED_AT);
            
            if (token) {
                this.currentToken = token;
                this.obtainedAt = obtainedAt ? new Date(obtainedAt) : null;
                this.lastSyncedAt = syncedAt ? new Date(syncedAt) : null;
                console.log('[TokenService] ✅ Loaded saved token from localStorage');
            }
        } catch (error) {
            console.warn('[TokenService] Could not load token from localStorage:', error);
        }
    }

    /**
     * Save token to localStorage
     */
    saveToken(token) {
        try {
            localStorage.setItem(this.STORAGE_KEY_TOKEN, token);
            localStorage.setItem(this.STORAGE_KEY_OBTAINED_AT, new Date().toISOString());
            console.log('[TokenService] 💾 Token saved to localStorage');
        } catch (error) {
            console.warn('[TokenService] Could not save token to localStorage:', error);
        }
    }

    /**
     * Save sync timestamp
     */
    saveSyncTimestamp() {
        try {
            localStorage.setItem(this.STORAGE_KEY_SYNCED_AT, new Date().toISOString());
        } catch (error) {
            console.warn('[TokenService] Could not save sync timestamp:', error);
        }
    }

    /**
     * Get service state
     */
    getState() {
        return {
            hasToken: this.currentToken !== null,
            tokenPreview: this.currentToken ? this.currentToken.substring(0, 20) + '...' : null,
            obtainedAt: this.obtainedAt?.toISOString() || null,
            lastSyncedAt: this.lastSyncedAt?.toISOString() || null,
            isValid: this.isTokenValid(),
            isStale: this.isTokenStale(),
            isDaysOld: this.getTokenAge(),
            syncedWithBackend: this.syncedWithBackend,
            autoRefreshEnabled: this.autoRefreshEnabled
        };
    }

    /**
     * Check if token is valid (format check)
     */
    isTokenValid() {
        if (!this.currentToken) return false;
        // FCM tokens are typically 152+ characters
        return typeof this.currentToken === 'string' && this.currentToken.length > 100;
    }

    /**
     * Check if token is stale
     */
    isTokenStale() {
        if (!this.obtainedAt) return true;
        const age = Date.now() - this.obtainedAt.getTime();
        return age > this.TOKEN_VALIDITY_MS;
    }

    /**
     * Get token age in days
     */
    getTokenAge() {
        if (!this.obtainedAt) return -1;
        const ageDays = (Date.now() - this.obtainedAt.getTime()) / (1000 * 60 * 60 * 24);
        return Math.round(ageDays * 10) / 10; // Round to 1 decimal
    }

    /**
     * Get current token
     */
    getCurrentToken() {
        return this.currentToken;
    }

    /**
     * Set new token (typically from plugin or refresh event)
     */
    setToken(token, markedSyncedWithBackend = false) {
        if (!token || typeof token !== 'string' || token.length < 100) {
            console.error('[TokenService] ❌ Invalid token format');
            return false;
        }

        const oldToken = this.currentToken;
        this.currentToken = token;
        this.obtainedAt = new Date();
        this.syncedWithBackend = markedSyncedWithBackend;
        
        // Save to storage
        this.saveToken(token);
        
        // Dispatch event for token change
        if (oldToken && oldToken !== token) {
            console.log('[TokenService] 🔄 Token changed (rotated by Firebase)');
            window.dispatchEvent(new CustomEvent('tokenChanged', {
                detail: { oldToken: oldToken, newToken: token }
            }));
        } else {
            console.log('[TokenService] ✅ New token set');
            window.dispatchEvent(new CustomEvent('tokenSet', {
                detail: { token: token }
            }));
        }
        
        return true;
    }

    /**
     * Sync token with backend
     */
    async syncWithBackend() {
        if (!this.currentToken) {
            console.log('[TokenService] ⚠️  No token to sync');
            return {
                success: false,
                error: 'NO_TOKEN',
                message: 'No token available to sync'
            };
        }

        try {
            // Get metadata
            const userId = document.querySelector('meta[name="user-id"]')?.content;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            if (!userId) {
                console.log('[TokenService] ⚠️  User ID not found');
                return {
                    success: false,
                    error: 'NO_USER_ID',
                    message: 'User not authenticated'
                };
            }

            console.log('[TokenService] 📤 Syncing token with backend...');

            const response = await fetch('/api/push/token', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || ''
                },
                body: JSON.stringify({
                    token: this.currentToken,
                    platform: this.getPlatform(),
                    user_id: parseInt(userId)
                })
            });

            if (!response.ok) {
                const errorText = await response.text();
                console.error(`[TokenService] ❌ Backend error: HTTP ${response.status}`);
                return {
                    success: false,
                    error: `HTTP_${response.status}`,
                    message: `Backend returned ${response.status}`,
                    data: { statusCode: response.status }
                };
            }

            const jsonResponse = await response.json();
            
            if (jsonResponse.success === true) {
                console.log('[TokenService] ✅ Token synced with backend');
                this.lastSyncedAt = new Date();
                this.syncedWithBackend = true;
                this.saveSyncTimestamp();
                
                return {
                    success: true,
                    message: 'Token synced',
                    data: jsonResponse
                };
            } else {
                console.error('[TokenService] ❌ Backend reported error:', jsonResponse.error);
                return {
                    success: false,
                    error: 'BACKEND_ERROR',
                    message: jsonResponse.error || 'Backend error',
                    data: jsonResponse
                };
            }

        } catch (error) {
            console.error('[TokenService] ❌ Network error:', error);
            return {
                success: false,
                error: 'NETWORK_ERROR',
                message: error.message,
                data: { originalError: error }
            };
        }
    }

    /**
     * Force refresh token from plugin
     */
    async forceRefreshToken() {
        try {
            const plugin = this.getFirebaseMessagingPlugin();
            if (!plugin) {
                console.error('[TokenService] Plugin not available');
                return {
                    success: false,
                    error: 'PLUGIN_UNAVAILABLE',
                    message: 'Firebase Messaging plugin not found'
                };
            }

            console.log('[TokenService] 🔄 Forcing token refresh from native plugin...');
            const result = await plugin.getToken();
            
            if (result?.token) {
                this.setToken(result.token, false); // Mark as not synced yet
                
                // Sync new token with backend
                const syncResult = await this.syncWithBackend();
                
                return {
                    success: true,
                    message: 'Token refreshed',
                    data: {
                        newToken: result.token,
                        backendSync: syncResult.success
                    }
                };
            } else {
                console.error('[TokenService] ❌ Plugin returned no token');
                return {
                    success: false,
                    error: 'NO_TOKEN_FROM_PLUGIN',
                    message: 'Plugin did not return a token'
                };
            }

        } catch (error) {
            console.error('[TokenService] ❌ Refresh error:', error);
            return {
                success: false,
                error: 'REFRESH_ERROR',
                message: error.message,
                data: { originalError: error }
            };
        }
    }

    /**
     * Start automatic token validity checks
     */
    startAutoRefresh() {
        if (this.refreshCheckInterval) {
            console.warn('[TokenService] Auto-refresh already running');
            return;
        }

        console.log('[TokenService] 🔄 Starting auto-refresh checks (every 1 hour)');
        this.autoRefreshEnabled = true;
        
        this.refreshCheckInterval = setInterval(() => {
            if (this.isTokenStale()) {
                console.log('[TokenService] ⚠️  Token is stale, triggering refresh...');
                this.forceRefreshToken();
            }
        }, this.REFRESH_CHECK_INTERVAL_MS);
    }

    /**
     * Stop automatic checks
     */
    stopAutoRefresh() {
        if (this.refreshCheckInterval) {
            clearInterval(this.refreshCheckInterval);
            this.refreshCheckInterval = null;
            this.autoRefreshEnabled = false;
            console.log('[TokenService] 🛑 Auto-refresh stopped');
        }
    }

    /**
     * Get Firebase Messaging plugin
     */
    getFirebaseMessagingPlugin() {
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
     * Get platform
     */
    getPlatform() {
        if (window.Capacitor?.getPlatform) {
            return window.Capacitor.getPlatform();
        }
        if (/android/i.test(navigator.userAgent)) return 'android';
        if (/iphone|ipad|ipod/i.test(navigator.userAgent)) return 'ios';
        return 'web';
    }

    /**
     * Clear token
     */
    clearToken() {
        this.currentToken = null;
        this.obtainedAt = null;
        this.lastSyncedAt = null;
        this.syncedWithBackend = false;
        
        try {
            localStorage.removeItem(this.STORAGE_KEY_TOKEN);
            localStorage.removeItem(this.STORAGE_KEY_OBTAINED_AT);
            localStorage.removeItem(this.STORAGE_KEY_SYNCED_AT);
        } catch (error) {
            console.warn('[TokenService] Could not clear localStorage:', error);
        }
        
        console.log('[TokenService] 🧹 Token cleared');
    }

    /**
     * Cleanup on destroy
     */
    destroy() {
        this.stopAutoRefresh();
        console.log('[TokenService] 🧹 Service destroyed');
    }
}

// ============================================
// GLOBAL INITIALIZATION
// ============================================

// Expose service to window
window.TokenService = null;

/**
 * Initialize token service on DOM ready
 */
function initializeTokenService() {
    window.TokenService = new TokenService();
    
    // Global API
    
    /**
     * Get current token
     */
    window.getDeviceToken = function() {
        if (!window.TokenService) return null;
        return window.TokenService.getCurrentToken();
    };

    /**
     * Sync token with backend
     */
    window.syncTokenWithBackend = async function() {
        if (!window.TokenService) {
            return { success: false, error: 'Service not initialized' };
        }
        return window.TokenService.syncWithBackend();
    };

    /**
     * Force token refresh
     */
    window.forceTokenRefresh = async function() {
        if (!window.TokenService) {
            return { success: false, error: 'Service not initialized' };
        }
        return window.TokenService.forceRefreshToken();
    };

    /**
     * Get token service state
     */
    window.getTokenServiceState = function() {
        if (!window.TokenService) return null;
        return window.TokenService.getState();
    };

    /**
     * Start auto-refresh
     */
    window.startTokenAutoRefresh = function() {
        if (!window.TokenService) return;
        window.TokenService.startAutoRefresh();
    };

    /**
     * Stop auto-refresh
     */
    window.stopTokenAutoRefresh = function() {
        if (!window.TokenService) return;
        window.TokenService.stopAutoRefresh();
    };

    /**
     * Clear token
     */
    window.clearDeviceToken = function() {
        if (!window.TokenService) return;
        window.TokenService.clearToken();
    };

    console.log('✅ [TokenService] Script loaded successfully');
    console.log('   Available functions:');
    console.log('   - window.getDeviceToken()');
    console.log('   - window.syncTokenWithBackend()');
    console.log('   - window.forceTokenRefresh()');
    console.log('   - window.getTokenServiceState()');
    console.log('   - window.startTokenAutoRefresh()');
    console.log('   - window.stopTokenAutoRefresh()');
    console.log('   - window.clearDeviceToken()');
    console.log('   Events:');
    console.log('   - tokenSet (when token is first set)');
    console.log('   - tokenChanged (when token is refreshed)');
}

// Listen for DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeTokenService);
} else {
    initializeTokenService();
}
