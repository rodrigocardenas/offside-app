@echo off
REM ============================================
REM Script de Deploy para Offside Club (Windows)
REM ============================================
REM Uso: deploy.bat [--clean-duplicates]
REM 
REM Este script carga las variables de entorno de .env.deploy
REM y ejecuta el deploy script de Bash

setlocal enabledelayedexpansion

echo.
echo ╔════════════════════════════════════════════════════════╗
echo ║          🚀 DEPLOYING OFFSIDE CLUB                    ║
echo ╚════════════════════════════════════════════════════════╝
echo.

REM Verificar que .env.deploy existe
if not exist ".env.deploy" (
    echo ❌ ERROR: .env.deploy no encontrado
    echo.
    echo Solución:
    echo   1. Copiar .env.deploy.example a .env.deploy
    echo   2. Editar .env.deploy con tu SSH_KEY_PATH
    echo   3. Intentar de nuevo
    echo.
    pause
    exit /b 1
)

REM Leer .env.deploy y establecer variables
echo 🔐 Leyendo configuración de despliegue...
for /f "tokens=1,2 delims==" %%a in ('type .env.deploy ^| findstr /v "^#" ^| findstr /v "^$"') do (
    set "line=%%a"
    if "!line:~0,6!"=="export" (
        set "line=!line:~7!"
        for /f "tokens=1,2 delims== " %%x in ("!line!") do (
            set "%%x=%%y"
            echo   ✓ %%x configurado
        )
    )
)

REM Verificar SSH_KEY_PATH
if not defined SSH_KEY_PATH (
    echo ❌ ERROR: SSH_KEY_PATH no está configurado
    echo   Verificar .env.deploy
    pause
    exit /b 1
)

if not exist "%SSH_KEY_PATH%" (
    echo ❌ ERROR: Archivo SSH key no encontrado
    echo   Ruta: %SSH_KEY_PATH%
    echo   Verificar que existe y es accesible
    pause
    exit /b 1
)

echo ✓ SSH key encontrado: %SSH_KEY_PATH%
echo.

REM Verificar si se debe limpiar duplicados
if "%1"=="--clean-duplicates" (
    set "CLEAN_DUPLICATES=true"
    echo ⚠️  Modo: LIMPIAR DUPLICADOS DE USUARIOS
    echo    Los usuarios duplicados serán eliminados
    echo.
) else (
    set "CLEAN_DUPLICATES=false"
    echo ℹ️  Modo: Normal (sin limpiar duplicados)
    echo    Para limpiar, usar: deploy.bat --clean-duplicates
    echo.
)

REM Ejecutar Bash script
echo 🚀 Iniciando deploy...
echo.

bash scripts/deploy.sh

if errorlevel 1 (
    echo.
    echo ❌ DEPLOY FALLÓ
    echo   Revisar logs arriba para más detalles
    pause
    exit /b 1
)

echo.
echo ✅ DEPLOY COMPLETADO EXITOSAMENTE
echo.
pause
