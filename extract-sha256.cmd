@echo off
REM Script para extraer SHA256 del keystore de Play Store
REM Uso: Ejecuta este archivo desde cmd.exe

echo ============================================
echo Extrayendo SHA256 del keystore de Play Store
echo ============================================
echo.

set KEYSTORE_PATH=C:\Users\rodri\offside.jks
set ALIAS=offside

if not exist "%KEYSTORE_PATH%" (
    echo ERROR: No encontré el keystore en: %KEYSTORE_PATH%
    echo Verifica que el archivo exista
    pause
    exit /b 1
)

echo Ingresa la contraseña de tu keystore de Play Store:
echo (Esta contraseña la usaste cuando subiste la app a Play Store)
echo.

REM Usar keytool para extraer el certificado
keytool -list -v -keystore "%KEYSTORE_PATH%" -alias %ALIAS%

echo.
echo ============================================
echo INSTRUCCIONES:
echo 1. Busca en la salida anterior: "SHA-256 fingerprint"
echo 2. Copia el valor (sin espacios, con los : entre los pares de números)
echo 3. Abre: public/.well-known/assetlinks.json
echo 4. Reemplaza "sha256_cert_fingerprints" con ese valor
echo ============================================
echo.

pause
