@echo off
REM Script para obtener SHA256 del keystore offside.jks

echo ====================================
echo Obteniendo SHA256 del keystore...
echo ====================================

keytool -list -v -keystore "C:\Users\rodri\offside.jks" -alias offside

echo.
echo ====================================
echo Copia el SHA256 que aparece arriba
echo ====================================
pause
