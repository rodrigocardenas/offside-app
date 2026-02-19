/**
 * Firebase Cloud Messaging - Native Capacitor 6 Implementation
 * 
 * PURPOSE: Handle push notifications using NATIVE Android/iOS FCM APIs via Capacitor 6
 * - NO polyfills for Service Workers or Notification API  
 * - NO Firebase Web SDK
 * - Direct access to native plugin methods
 * - Proper permission handling for Android 13+
 * - Message listeners for real-time handling
 * 
 * Author: Senior Hybrid Architecture Expert
 * Date: February 2026 (REFACTORED)
 * Version: 2.0 - Capacitor 6 Namespace Fixes + Listeners
 */

class NativeFirebaseMessagingService {
    constructor() {
        this.platform = this.detectPlatform();
        this.isAndroid = this.platform === 'android';
        this.isWeb = this.platform === 'web';
        this.plugin = null;
        this.initialized = false;
        this.tokenRefreshListener = null;
        this.messageListener = null;
        
        // Debug logging
        this.logs = [];
        this.addLog('🎯 Service initialized (v2.0)', 'info');
        this.addLog(`Platform detected: ${this.platform}`, 'info');
    }

    /**
     * Detect platform (web, android, ios)
     */
    detectPlatform() {
        if (typeof window === 'undefined') return 'unknown';
        
        // Check for Capacitor runtime
        if (window.Capacitor && window.Capacitor.isNativePlatform && window.Capacitor.isNativePlatform()) {
            return window.Capacitor.getPlatform();
        }
        
        // Fallback: User Agent detection
        if (/android/i.test(navigator.userAgent)) return 'android';
        if (/iphone|ipad|ipod/i.test(navigator.userAgent)) return 'ios';
        return 'web';
    }

    /**
     * Add debug log entry
     */
    addLog(message, type = 'log') {
        const timestamp = new Date().toLocaleTimeString();
        const prefix = {
            'info': '✓',
            'warn': '⚠',
            'error': '❌',
            'log': '📌'
        }[type] || '•';
        
        const logEntry = `[${timestamp}] ${prefix} ${message}`;
        this.logs.push(logEntry);
        console.log(logEntry);
        
        // Update debug panel if exists
        this.updateDebugPanel();
    }

    /**
     * Update debug panel in UI
     */
    updateDebugPanel() {
        const debugEl = document.getElementById('globalDebugOutput');
        if (debugEl) {
            debugEl.innerHTML = this.logs.map(l => `<div>${l}</div>`).join('');
            debugEl.scrollTop = debugEl.scrollHeight;
        }
    }

    /**
     * Initialize the service
     * For Android: Get plugin reference and request permissions
     * For Web/iOS: No-op (or fallback to other methods)
     */
    async initialize() {
        if (this.initialized) {
            this.addLog('Service already initialized', 'warn');
            return;
        }

        if (this.isAndroid) {
            return this.initializeAndroid();
        } else if (this.isWeb) {
            this.addLog('Web platform: Push notifications not supported in browser', 'warn');
            return;
        } else if (this.platform === 'ios') {
            return this.initializeIos();
        }
    }

    /**
     * Initialize for Android
     * 1. Get native plugin reference
     * 2. Check permissions (POST_NOTIFICATIONS on Android 13+)
     * 3. Request permissions if needed
     * 4. Request device token
     * 5. Setup message listeners
     */
    async initializeAndroid() {
        try {
            this.addLog('🤖 Android: Initializing Firebase Messaging...', 'info');

            // Step 1: Get plugin reference
            const plugin = this.getFirebaseMessagingPlugin();
            
            if (!plugin) {
                this.addLog('❌ Android: Firebase Messaging plugin not found', 'error');
                this.addLog('   FIX: Run "npx cap sync android" and rebuild the APK', 'warn');
                this.addLog('   Ensure @capacitor-firebase/messaging@^6.3.1 is installed in package.json', 'warn');
                return { success: false, error: 'Plugin not accessible' };
            }

            this.plugin = plugin;
            this.addLog('✅ Android: Plugin reference obtained', 'info');

            // Step 2: Check current permissions
            this.addLog('📋 Android: Checking POST_NOTIFICATIONS permission...', 'info');
            const permissionStatus = await this.checkPermissions();
            this.addLog(`   Status: ${permissionStatus?.display || 'unknown'}`, 'log');

            // Step 3: Request permissions if not granted (Android 13+)
            if (permissionStatus?.display !== 'granted') {
                this.addLog('📲 Android: Permission not granted, requesting from user...', 'info');
                const requestResult = await this.requestPermissions();
                this.addLog(`   User response: ${requestResult?.display || 'denied'}`, 'log');
                
                if (requestResult?.display !== 'granted') {
                    this.addLog('⚠️  Android: User denied POST_NOTIFICATIONS permission', 'warn');
                    this.addLog('   Notifications may not be displayed. User can enable in settings.', 'warn');
                    // Don't return false - continue anyway, some devices may still receive
                }
            } else {
                this.addLog('✅ Android: POST_NOTIFICATIONS permission already granted', 'info');
            }

            // Step 4: Request token
            this.addLog('🔑 Android: Requesting FCM token from native layer...', 'info');
            const tokenResult = await this.requestTokenFromNative();
            
            if (tokenResult?.success) {
                this.addLog('✅ Android: Initialization completed successfully', 'info');
                this.initialized = true;
                return tokenResult;
            } else {
                this.addLog(`❌ Android: Token request failed: ${tokenResult?.error}`, 'error');
                return tokenResult;
            }

        } catch (error) {
            this.addLog(`❌ Android: Initialization failed: ${error.message}`, 'error');
            return { success: false, error: error.message };
        }
    }

    /**
     * Initialize for iOS
     * Similar to Android but with iOS-specific handling
     */
    async initializeIos() {
        try {
            this.addLog('🍎 iOS: Initializing Firebase Messaging...', 'info');
            
            const plugin = this.getFirebaseMessagingPlugin();
            if (!plugin) {
                this.addLog('❌ iOS: Firebase Messaging plugin not found', 'error');
                this.addLog('   FIX: Run "npx cap sync ios" and rebuild the app', 'warn');
                return { success: false, error: 'Plugin not accessible' };
            }

            this.plugin = plugin;
            this.addLog('✅ iOS: Plugin reference obtained', 'info');

            // iOS typically shows permission dialog automatically, but we check anyway
            this.addLog('📋 iOS: Checking notification permission...', 'info');
            const permissionStatus = await this.checkPermissions();
            this.addLog(`   Status: ${permissionStatus?.display || 'unknown'}`, 'log');

            if (permissionStatus?.display !== 'granted') {
                this.addLog('📲 iOS: Requesting notification permission...', 'info');
                const requestResult = await this.requestPermissions();
                this.addLog(`   User response: ${requestResult?.display || 'denied'}`, 'log');
            } else {
                this.addLog('✅ iOS: Notification permission already granted', 'info');
            }

            // Request token
            this.addLog('🔑 iOS: Requesting APNS token from native layer...', 'info');
            const tokenResult = await this.requestTokenFromNative();
            
            if (tokenResult?.success) {
                this.addLog('✅ iOS: Initialization completed successfully', 'info');
                this.initialized = true;
                return tokenResult;
            } else {
                this.addLog(`❌ iOS: Token request failed: ${tokenResult?.error}`, 'error');
                return tokenResult;
            }

        } catch (error) {
            this.addLog(`❌ iOS: Initialization failed: ${error.message}`, 'error');
            return { success: false, error: error.message };
        }
    }

    /**
     * Get Firebase Messaging plugin reference
     * Capacitor 6 CORRECTED: Uses FirebaseMessaging namespace (NOT Messaging)
     */
    getFirebaseMessagingPlugin() {
        // Path 1: Capacitor.Plugins.FirebaseMessaging (Capacitor 6 CORRECT NAMESPACE)
        if (window.Capacitor?.Plugins?.FirebaseMessaging) {
            this.addLog('✅ Plugin found at: Capacitor.Plugins.FirebaseMessaging (CORRECT)', 'log');
            return window.Capacitor.Plugins.FirebaseMessaging;
        }

        // Path 2: window.plugins.FirebaseMessaging (legacy injection)
        if (window.plugins?.FirebaseMessaging) {
            this.addLog('⚠️  Plugin found at: window.plugins.FirebaseMessaging (legacy)', 'warn');
            return window.plugins.FirebaseMessaging;
        }

        // Path 3: FirebaseMessaging directly on window (auto-injected)
        if (window.FirebaseMessaging) {
            this.addLog('⚠️  Plugin found at: window.FirebaseMessaging (direct)', 'warn');
            return window.FirebaseMessaging;
        }

        // Path 4: Check for Messaging (INCORRECT but keep as fallback)
        if (window.Capacitor?.Plugins?.Messaging) {
            this.addLog('❌ WARNING: Found Capacitor.Plugins.Messaging (INCORRECT namespace)', 'error');
            this.addLog('This is the WRONG plugin. Looking for FirebaseMessaging instead.', 'error');
            return null;
        }

        this.addLog('❌ Firebase Messaging plugin NOT FOUND at any expected path', 'error');
        this.addLog('Verify that @capacitor-firebase/messaging is installed and properly synced', 'error');
        this.addLog('Run: npx cap sync android && npx cap sync ios', 'warn');
        return null;
    }

    /**
     * Check current notification permissions
     */
    async checkPermissions() {
        try {
            if (!this.plugin) return { display: 'denied' };
            
            const result = await this.plugin.checkPermissions();
            return result;
        } catch (error) {
            this.addLog(`checkPermissions error: ${error.message}`, 'error');
            return { display: 'denied' };
        }
    }

    /**
     * Request notification permissions
     * For Android 13+, this will show system dialog for POST_NOTIFICATIONS
     */
    async requestPermissions() {
        try {
            if (!this.plugin) {
                this.addLog('Plugin not available for requestPermissions', 'error');
                return { display: 'denied' };
            }
            
            const result = await this.plugin.requestPermissions();
            return result;
        } catch (error) {
            this.addLog(`requestPermissions error: ${error.message}`, 'error');
            return { display: 'denied' };
        }
    }

    /**
     * Request FCM device token from native layer
     * This bypasses Firebase Web SDK entirely and gets token directly from Android
     */
    async requestTokenFromNative() {
        try {
            if (!this.plugin) {
                this.addLog('Plugin not available for token request', 'error');
                return { success: false, error: 'Plugin unavailable' };
            }

            this.addLog('Calling plugin.getToken()...', 'info');
            const result = await this.plugin.getToken();
            
            if (result && result.token) {
                this.addLog(`🔑 FCM Token obtained: ${result.token.substring(0, 30)}...`, 'info');
                
                // Register token with backend
                const registrationResult = await this.registerTokenWithBackend(result.token);
                
                // Set up listeners for incoming messages
                await this.setupMessageListeners();
                
                return { success: true, token: result.token, source: 'native' };
            } else {
                this.addLog('⚠️  getToken() returned empty result', 'warn');
                return { success: false, error: 'No token in result' };
            }

        } catch (error) {
            this.addLog(`❌ Failed to get token: ${error.message}`, 'error');
            return { success: false, error: error.message };
        }
    }

    /**
     * Setup listeners for incoming push messages
     * Handles both foreground and background message reception
     */
    async setupMessageListeners() {
        try {
            if (!this.plugin) {
                this.addLog('Plugin not available for setting up listeners', 'error');
                return false;
            }

            // Listen for message received events
            this.messageListener = await this.plugin.addListener('messageReceived', (message) => {
                this.handleMessageReceived(message);
            });

            this.addLog('📨 Message listener registered for foreground messages', 'info');

            // Listen for token refresh events
            this.tokenRefreshListener = await this.plugin.addListener('tokenReceived', (result) => {
                this.handleTokenRefresh(result);
            });

            this.addLog('🔄 Token refresh listener registered', 'info');
            return true;

        } catch (error) {
            this.addLog(`⚠️  Could not set up message listeners: ${error.message}`, 'warn');
            this.addLog('Messages may not be handled in foreground on this platform', 'warn');
            return false;
        }
    }

    /**
     * Handle incoming message
     * Called when app receives a push notification while in foreground
     */
    handleMessageReceived(message) {
        this.addLog('📬 Message RECEIVED (Foreground):', 'info');
        this.addLog(`   Title: ${message?.notification?.title || 'N/A'}`, 'log');
        this.addLog(`   Body: ${message?.notification?.body?.substring(0, 50) || 'N/A'}...`, 'log');

        if (message?.data) {
            this.addLog(`   Data: ${JSON.stringify(message.data)}`, 'log');
        }

        // Dispatch custom event so app can listen to it
        const eventDetail = {
            title: message?.notification?.title,
            body: message?.notification?.body,
            data: message?.data || {}
        };

        window.dispatchEvent(new CustomEvent('pushMessageReceived', {
            detail: eventDetail,
            bubbles: true,
            cancelable: true
        }));

        this.addLog('✅ Custom event "pushMessageReceived" dispatched', 'log');
    }

    /**
     * Handle token refresh
     * Called when FCM rota the device token (periodic or forced)
     */
    async handleTokenRefresh(result) {
        this.addLog('🔄 Token REFRESHED by Firebase:', 'info');
        
        if (result?.token) {
            this.addLog(`   New Token: ${result.token.substring(0, 30)}...`, 'log');
            
            // Re-register the new token with backend
            await this.registerTokenWithBackend(result.token);
            
            // Dispatch event for app to handle
            window.dispatchEvent(new CustomEvent('pushTokenRefreshed', {
                detail: { token: result.token },
                bubbles: true,
                cancelable: true
            }));
        }
    }

    /**
     * Register token with Laravel backend
     * Validates response format and logs detailed results
     */
    async registerTokenWithBackend(token) {
        try {
            // Validate token integrity
            if (!token || typeof token !== 'string' || token.length < 50) {
                this.addLog('❌ Token validation failed: Invalid token format', 'error');
                return { success: false, error: 'Invalid token format' };
            }

            const userId = document.querySelector('meta[name="user-id"]')?.content;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            if (!userId) {
                this.addLog('⚠️  User ID not found in meta tag. Token not registered.', 'warn');
                this.addLog('Add: <meta name="user-id" content="{{ auth()->id() ?? "anonymous" }}" />', 'warn');
                return { success: false, error: 'No user ID' };
            }

            this.addLog(`📤 Registering token with backend...`, 'info');
            this.addLog(`   User ID: ${userId}`, 'log');
            this.addLog(`   Platform: ${this.platform}`, 'log');
            this.addLog(`   Token: ${token.substring(0, 30)}...`, 'log');

            const response = await fetch('/api/push/token', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || ''
                },
                body: JSON.stringify({
                    token: token,
                    platform: this.platform,
                    user_id: parseInt(userId)
                })
            });

            // Validate HTTP response
            if (!response.ok) {
                const errorText = await response.text();
                this.addLog(`❌ HTTP ${response.status} from backend: ${errorText || 'No error details'}`, 'error');
                return { success: false, error: `HTTP ${response.status}` };
            }

            // Parse and validate JSON response
            let jsonResponse;
            try {
                jsonResponse = await response.json();
            } catch (e) {
                this.addLog(`❌ Invalid JSON response from backend`, 'error');
                return { success: false, error: 'Invalid JSON response' };
            }

            // Check success flag in response
            if (jsonResponse.success === true) {
                this.addLog(`✅ Token successfully registered with backend`, 'info');
                if (jsonResponse.message) {
                    this.addLog(`   Message: ${jsonResponse.message}`, 'log');
                }
                return { success: true, response: jsonResponse };
            } else {
                this.addLog(`❌ Backend reported failure: ${jsonResponse.error || jsonResponse.message || 'Unknown error'}`, 'error');
                return { success: false, error: jsonResponse.error || 'Backend error' };
            }

        } catch (error) {
            this.addLog(`❌ Network error registering token: ${error.message}`, 'error');
            return { success: false, error: `Network error: ${error.message}` };
        }
    }

    /**
     * Manual token request
     * User can call this to refresh token or on-demand
     */
    async requestTokenManually() {
        try {
            this.addLog('Manual token request initiated...', 'info');

            if (!this.initialized) {
                this.addLog('Service not initialized, running initialization first...', 'warn');
                await this.initialize();
            }

            if (!this.plugin) {
                this.addLog('Plugin still not available', 'error');
                return { success: false, error: 'Plugin unavailable' };
            }

            return await this.requestTokenFromNative();

        } catch (error) {
            this.addLog(`Manual token request failed: ${error.message}`, 'error');
            return { success: false, error: error.message };
        }
    }

    /**
     * Clean up listeners
     * Call this when app is unloading or service is being destroyed
     */
    destroy() {
        if (this.messageListener) {
            this.messageListener.remove();
            this.addLog('🧹 Message listener removed', 'info');
        }
        if (this.tokenRefreshListener) {
            this.tokenRefreshListener.remove();
            this.addLog('🧹 Token refresh listener removed', 'info');
        }
        this.initialized = false;
    }

    /**
     * Get current logs (for debugging)
     */
    getLogs() {
        return this.logs;
    }

    /**
     * Clear logs
     */
    clearLogs() {
        this.logs = [];
        this.updateDebugPanel();
    }

    /**
     * Export service state for debugging
     */
    getState() {
        return {
            platform: this.platform,
            isAndroid: this.isAndroid,
            isWeb: this.isWeb,
            initialized: this.initialized,
            pluginAvailable: this.plugin !== null,
            messageListenerActive: this.messageListener !== null,
            tokenRefreshListenerActive: this.tokenRefreshListener !== null,
            logsCount: this.logs.length
        };
    }
}

// ============================================
// GLOBAL INITIALIZATION & API
// ============================================

// Expose service to window
window.NativeFirebaseMessaging = null;

// ===== DEFINE GLOBAL API FUNCTIONS IMMEDIATELY (before DOMContentLoaded) =====
// This ensures functions are available even if DOMContentLoaded timing is off

/**
 * Initialize push notifications
 * Usage: await window.initializePushNotifications()
 */
window.initializePushNotifications = async function() {
    if (!window.NativeFirebaseMessaging) {
        console.error('[FCM] Service not initialized yet');
        return { success: false, error: 'Service not initialized yet' };
    }
    const result = await window.NativeFirebaseMessaging.initialize();
    return result;
};

/**
 * Request push token manually
 * Usage: await window.requestPushToken()
 */
window.requestPushToken = async function() {
    if (!window.NativeFirebaseMessaging) {
        console.error('[FCM] Service not initialized yet');
        return { success: false, error: 'Service not initialized yet' };
    }
    return await window.NativeFirebaseMessaging.requestTokenManually();
};

/**
 * Get service state for debugging
 * Usage: window.getPushNotificationState()
 */
window.getPushNotificationState = function() {
    if (!window.NativeFirebaseMessaging) {
        console.error('[FCM] Service not initialized yet - returning placeholder state');
        return { error: 'Service not initialized yet', initialized: false };
    }
    return window.NativeFirebaseMessaging.getState();
};

/**
 * Get all logs
 * Usage: window.getPushNotificationLogs()
 */
window.getPushNotificationLogs = function() {
    if (!window.NativeFirebaseMessaging) {
        return [];
    }
    return window.NativeFirebaseMessaging.getLogs();
};

/**
 * Clear logs
 * Usage: window.clearPushNotificationLogs()
 */
window.clearPushNotificationLogs = function() {
    if (!window.NativeFirebaseMessaging) {
        return;
    }
    window.NativeFirebaseMessaging.clearLogs();
};

/**
 * Check if initialized
 * Usage: window.isPushNotificationInitialized()
 */
window.isPushNotificationInitialized = function() {
    if (!window.NativeFirebaseMessaging) {
        return false;
    }
    return window.NativeFirebaseMessaging.initialized;
};

// ===== THESE ARE DEFINED IMMEDIATELY ===== 
console.log('✅ Firebase Messaging API functions registered to window');
console.log('   Available NOW: window.getPushNotificationState, etc');

// Document Ready Handler - Creates the service instance
function initializeOnDOMReady() {
    console.log('🔄 DOMContentLoaded - Creating service instance...');
    
    // Create service instance
    window.NativeFirebaseMessaging = new NativeFirebaseMessagingService();
    
    console.log('✅ Service instance created - initializing platform-specific handlers...');
    
    // ===== AUTO-INITIALIZATION =====
    
    // Auto-initialize if on native platform
    if (window.NativeFirebaseMessaging.isAndroid || window.NativeFirebaseMessaging.platform === 'ios') {
        window.NativeFirebaseMessaging.addLog('🚀 Auto-initializing on native platform...', 'info');
        window.NativeFirebaseMessaging.initialize().then((result) => {
            if (result?.success) {
                window.NativeFirebaseMessaging.addLog('✅ Auto-initialization completed', 'info');
            } else {
                window.NativeFirebaseMessaging.addLog('⚠️  Auto-initialization reported issue', 'warn');
            }
        }).catch((error) => {
            window.NativeFirebaseMessaging.addLog(`❌ Auto-initialization error: ${error}`, 'error');
        });
    } else {
        window.NativeFirebaseMessaging.addLog('ℹ️  Not on native platform - skipping auto-initialization', 'warn');
    }

    // ===== DEBUG INFO =====
    
    console.log('✅ [Firebase Messaging (Native)] Fully Initialized');
    console.log('   Version: 2.0 (Capacitor 6 - Fixed Namespaces)');
    console.log('   Available functions:');
    console.log('   - window.initializePushNotifications()');
    console.log('   - window.requestPushToken()');
    console.log('   - window.getPushNotificationState()');
    console.log('   - window.getPushNotificationLogs()');
    console.log('   - window.clearPushNotificationLogs()');
    console.log('   - window.isPushNotificationInitialized()');
    console.log('   Events:');
    console.log('   - pushMessageReceived (when message arrives in foreground)');
    console.log('   - pushTokenRefreshed (when FCM rotates token)');
}

// Listen for DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeOnDOMReady);
} else {
    // DOM already loaded - call immediately
    console.log('⚡ DOM already loaded - initializing immediately');
    initializeOnDOMReady();
}


