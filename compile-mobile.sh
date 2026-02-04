#!/bin/bash

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}=== Offside Club - Mobile Compilation Setup ===${NC}\n"

# 1. Check google-services.json
echo -e "${YELLOW}1. Verificando google-services.json...${NC}"
if [ ! -f "android/app/google-services.json" ]; then
    echo -e "${RED}❌ android/app/google-services.json NO EXISTE${NC}"
    echo -e "${YELLOW}SOLUCIÓN: Descarga el archivo de Firebase Console:${NC}"
    echo -e "  1. Ve a: https://console.firebase.google.com"
    echo -e "  2. Proyecto: offside-dd226"
    echo -e "  3. Configuración → Configuración del proyecto → Apps"
    echo -e "  4. Android app: offside-dd226"
    echo -e "  5. Descarga: google-services.json"
    echo -e "  6. Pega en: android/app/google-services.json"
    exit 1
else
    echo -e "${GREEN}✓ google-services.json ENCONTRADO${NC}"
fi

# 2. Check .env file
echo -e "\n${YELLOW}2. Verificando .env...${NC}"
if [ ! -f ".env" ]; then
    echo -e "${RED}❌ .env NO EXISTE${NC}"
    exit 1
else
    echo -e "${GREEN}✓ .env ENCONTRADO${NC}"
fi

# 3. Install dependencies
echo -e "\n${YELLOW}3. Instalando dependencias npm...${NC}"
npm install
if [ $? -ne 0 ]; then
    echo -e "${RED}❌ npm install falló${NC}"
    exit 1
fi

# 4. Sync Capacitor
echo -e "\n${YELLOW}4. Sincronizando con Capacitor...${NC}"
npx cap sync android
if [ $? -ne 0 ]; then
    echo -e "${RED}❌ cap sync android falló${NC}"
    exit 1
fi

# 5. Build APK
echo -e "\n${YELLOW}5. Compilando APK Debug...${NC}"
echo -e "${BLUE}Esto puede tomar 5-10 minutos...${NC}\n"
cd android
./gradlew assembleDebug
BUILD_RESULT=$?
cd ..

if [ $BUILD_RESULT -ne 0 ]; then
    echo -e "${RED}❌ Compilación falló${NC}"
    exit 1
fi

APK_PATH="android/app/build/outputs/apk/debug/app-debug.apk"
if [ -f "$APK_PATH" ]; then
    echo -e "${GREEN}✓ APK compilado exitosamente${NC}"
    echo -e "${BLUE}Ubicación: $APK_PATH${NC}\n"
    
    # 6. Ask for device/emulator
    echo -e "${YELLOW}6. Instalando en dispositivo/emulador...${NC}"
    echo -e "${BLUE}¿Tienes adb disponible? (adb devices)${NC}"
    
    if command -v adb &> /dev/null; then
        adb devices
        echo -e "\n${YELLOW}Ejecutando: adb install -r $APK_PATH${NC}"
        adb install -r "$APK_PATH"
        
        if [ $? -eq 0 ]; then
            echo -e "${GREEN}✓ APK instalado exitosamente${NC}"
            echo -e "${BLUE}La app debería aparecer en tu dispositivo como 'Offside Club'${NC}\n"
            
            # 7. Testing instructions
            echo -e "${YELLOW}7. Testing - Próximos pasos:${NC}"
            echo -e "  ${GREEN}Bug 1 - Android Back Button:${NC}"
            echo -e "    • Abre la app"
            echo -e "    • Navega entre pantallas"
            echo -e "    • Presiona botón atrás → debe navegar a pantalla anterior"
            echo -e "    • Presiona en home → debe mostrar diálogo de salida\n"
            
            echo -e "  ${GREEN}Bug 2 - Deep Links:${NC}"
            echo -e "    • Copia este link: offsideclub://group/1"
            echo -e "    • Envía por navegador o adb: adb shell am start -a android.intent.action.VIEW -d \"offsideclub://group/1\""
            echo -e "    • Debe abrir la app (no el navegador) e ir al grupo\n"
            
            echo -e "  ${GREEN}Bug 3 - Firebase Notifications:${NC}"
            echo -e "    • Abre DevTools en web (F12)"
            echo -e "    • Crea una pregunta predictiva"
            echo -e "    • Verifica que notificación llega en mobile\n"
            
            echo -e "  ${GREEN}Bug 4 - Cache Issues:${NC}"
            echo -e "    • Actualiza datos en web"
            echo -e "    • Verifica que aparecen automáticamente en mobile\n"
            
            echo -e "${YELLOW}Logs en tiempo real:${NC}"
            echo -e "  adb logcat | grep -E 'DeepLinks|AndroidBackButton|FirebaseMessaging|offsideclub'\n"
        else
            echo -e "${RED}❌ Instalación falló${NC}"
            echo -e "${YELLOW}Intenta manualmente:${NC}"
            echo -e "  adb install -r $APK_PATH"
        fi
    else
        echo -e "${YELLOW}adb no disponible. Instalación manual:${NC}"
        echo -e "  1. Conecta dispositivo/emulador"
        echo -e "  2. Ejecuta: adb install -r $APK_PATH"
        echo -e "  3. O arrastra el APK a Android Studio\n"
    fi
else
    echo -e "${RED}❌ APK no se creó${NC}"
    exit 1
fi

echo -e "${GREEN}=== Compilación Completada ===${NC}\n"
