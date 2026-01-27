#!/bin/bash

# Script para compilar y probar el fix del Android Back Button (Bug #1)
# Uso: ./test-android-back-button.sh [build|run|sync]

set -e

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$PROJECT_DIR"

echo "=========================================="
echo "  Testing Android Back Button Fix"
echo "=========================================="
echo ""

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Functions
log_step() {
    echo -e "${YELLOW}[STEP]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Get action from argument
ACTION="${1:-build}"

case "$ACTION" in
    build)
        log_step "Building Vue/Alpine assets..."
        npm run build
        log_success "Assets built successfully"
        
        log_step "Syncing with Capacitor..."
        npx cap sync android
        log_success "Capacitor sync completed"
        
        log_step "Opening Android Studio..."
        npx cap open android
        log_success "Android Studio should open. Run the app from there."
        ;;
        
    run)
        log_step "Building assets..."
        npm run build
        
        log_step "Syncing with Capacitor..."
        npx cap sync android
        
        log_step "Building APK for testing..."
        cd android
        ./gradlew assembleDebug
        log_success "APK built successfully"
        
        log_step "Running on connected device/emulator..."
        adb install -r app/build/outputs/apk/debug/app-debug.apk
        adb shell am start -n com.offsideclub.app/.MainActivity
        log_success "App launched!"
        ;;
        
    sync)
        log_step "Building assets..."
        npm run build
        
        log_step "Syncing files to Android project..."
        npx cap sync android
        log_success "Sync completed"
        ;;
        
    test-web)
        log_step "Building for web testing..."
        npm run build
        
        log_step "Starting development server..."
        npm run dev
        log_success "Dev server running. Open http://localhost in your browser"
        log_step "The Android handler will NOT run in web mode - that's expected!"
        ;;
        
    logs)
        log_step "Showing logcat from connected device..."
        adb logcat | grep -E "(AndroidBackButton|offsideclub|error|Exception)" || true
        ;;
        
    *)
        echo "Usage: $0 [command]"
        echo ""
        echo "Commands:"
        echo "  build      - Build assets and open Android Studio (default)"
        echo "  run        - Build, compile, and install on device/emulator"
        echo "  sync       - Sync files without opening Android Studio"
        echo "  test-web   - Start dev server for web testing"
        echo "  logs       - Show logcat from connected device"
        echo ""
        echo "Testing Checklist:"
        echo "  1. Run: $0 build"
        echo "  2. Use Android Studio to run the app"
        echo "  3. Navigate to several pages"
        echo "  4. Press Android back button - should go to previous page"
        echo "  5. Continue pressing until home"
        echo "  6. Press back from home - should show exit dialog"
        exit 1
        ;;
esac

echo ""
echo "=========================================="
echo "  Done!"
echo "=========================================="
