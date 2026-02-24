/**
 * ULTRA SIMPLE TEST - Solo logging, sin clases
 */

console.log('🎯 === FIREBASE MESSAGING TEST SCRIPT LOADED ===');
console.log('📍 timestamp:', new Date().toISOString());
console.log('📍 document.readyState:', document.readyState);
console.log('📍 typeof window:', typeof window);
console.log('📍 typeof navigator:', typeof navigator);
console.log('📍 typeof Capacitor:', typeof window.Capacitor);

// Define function IMMEDIATELY before anything else
window.testFirebaseLoaded = function() {
    return {
        loaded: true,
        timestamp: new Date().toISOString(),
        readyState: document.readyState,
        hasNavigator: typeof navigator !== 'undefined',
        hasCapacitor: typeof window.Capacitor !== 'undefined'
    };
};

console.log('✅ testFirebaseLoaded() is NOW defined - test: ' + typeof window.testFirebaseLoaded);

// Now try to call it
try {
    const result = window.testFirebaseLoaded();
    console.log('✅ Function works! Result:', JSON.stringify(result));
} catch (e) {
    console.error('❌ Error calling testFirebaseLoaded:', e.message);
}

console.log('🎯 === TEST SCRIPT ENDED ===');
