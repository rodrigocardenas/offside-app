#!/bin/bash

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}=== Offside Club - Complete Mobile Build Setup ===${NC}\n"

# 1. Install npm dependencies
echo -e "${YELLOW}1. Installing npm dependencies...${NC}"
npm install
if [ $? -ne 0 ]; then
    echo -e "${RED}❌ npm install failed${NC}"
    exit 1
fi
echo -e "${GREEN}✓ npm dependencies installed${NC}\n"

# 2. Sync Capacitor
echo -e "${YELLOW}2. Syncing Capacitor with Android...${NC}"
npx cap sync android
if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Capacitor sync failed${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Capacitor synced${NC}\n"

# 3. Check google-services.json
echo -e "${YELLOW}3. Checking google-services.json...${NC}"
if [ ! -f "android/app/google-services.json" ]; then
    echo -e "${RED}❌ android/app/google-services.json NOT FOUND${NC}"
    echo -e "${YELLOW}Solution:${NC}"
    echo -e "  1. Go to: https://console.firebase.google.com"
    echo -e "  2. Project: offside-dd226"
    echo -e "  3. Download: google-services.json"
    echo -e "  4. Copy to: android/app/google-services.json"
    echo -e "  5. Run this script again"
    exit 1
fi
echo -e "${GREEN}✓ google-services.json found${NC}\n"

# 4. Clean and build
echo -e "${YELLOW}4. Building APK (this may take 5-10 minutes)...${NC}"
cd android
./gradlew clean
if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Gradle clean failed${NC}"
    exit 1
fi

./gradlew assembleDebug
BUILD_RESULT=$?
cd ..

if [ $BUILD_RESULT -ne 0 ]; then
    echo -e "${RED}❌ Build failed${NC}"
    exit 1
fi

APK_PATH="android/app/build/outputs/apk/debug/app-debug.apk"
if [ -f "$APK_PATH" ]; then
    echo -e "${GREEN}✓ APK built successfully${NC}\n"
    
    echo -e "${BLUE}=== Build Complete ===${NC}"
    echo -e "${GREEN}APK Location: $APK_PATH${NC}\n"
    
    echo -e "${YELLOW}Next steps:${NC}"
    echo -e "  1. Connect Android device/emulator"
    echo -e "  2. Run: adb install -r $APK_PATH"
    echo -e "  3. Test on device"
    
    echo -e "\n${YELLOW}View logs:${NC}"
    echo -e "  adb logcat | grep -E 'DeepLinks|AndroidBackButton|Firebase'"
else
    echo -e "${RED}❌ APK not created${NC}"
    exit 1
fi
