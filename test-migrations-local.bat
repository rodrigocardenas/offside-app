@echo off
REM ============================================================================
REM Local Migration Testing Script (Windows)
REM ============================================================================
REM This script helps test migrations locally before production deployment
REM
REM Usage:
REM   test-migrations-local.bat [option]
REM
REM Options:
REM   fresh      - Rollback all and run fresh (for corrupted databases)
REM   incremental - Run only new migrations
REM   status     - Check migration status
REM   verify     - Verify database after deployment
REM   help       - Show help message
REM
REM ============================================================================

setlocal enabledelayedexpansion
cd /d "%~dp0"

REM Colors for output (Windows 10+)
set "GREEN=[92m"
set "RED=[91m"
set "YELLOW=[93m"
set "BLUE=[94m"
set "RESET=[0m"

echo %BLUE%========================================%RESET%
echo %BLUE%Local Migration Testing Script%RESET%
echo %BLUE%========================================%RESET%
echo.

REM Check if artisan exists
if not exist "artisan" (
    echo %RED%Error: artisan file not found in current directory%RESET%
    echo Please run this script from the root of the Laravel project
    pause
    exit /b 1
)

REM Get the option
set "option=%1"
if "%option%"=="" set "option=help"

if "%option%"=="fresh" goto fresh
if "%option%"=="incremental" goto incremental
if "%option%"=="status" goto status
if "%option%"=="verify" goto verify
if "%option%"=="help" goto help
goto unknown

:fresh
echo %BLUE%Fresh Migration (Rollback All + Migrate)%RESET%
echo %YELLOW%Warning: This will rollback ALL migrations%RESET%
echo %YELLOW%Only use if database schema is corrupted%RESET%
set /p confirm="Continue? (yes/no): "
if /i not "%confirm%"=="yes" (
    echo Cancelled
    goto end
)

echo Rollbacking all migrations...
call php artisan migrate:rollback --all --force
if errorlevel 1 (
    echo %RED%Failed to rollback migrations%RESET%
    pause
    exit /b 1
)
echo %GREEN%All migrations rolled back%RESET%

echo Running fresh migrations...
call php artisan migrate --force
if errorlevel 1 (
    echo %RED%Fresh migrations failed%RESET%
    pause
    exit /b 1
)
echo %GREEN%Fresh migrations completed%RESET%
goto verify_status

:incremental
echo %BLUE%Incremental Migration%RESET%
echo Running pending migrations...
call php artisan migrate --force
if errorlevel 1 (
    echo %RED%Incremental migrations failed%RESET%
    pause
    exit /b 1
)
echo %GREEN%Incremental migrations completed%RESET%
goto verify_status

:status
echo %BLUE%Migration Status%RESET%
call php artisan migrate:status
goto end

:verify_status
echo.
echo %BLUE%========================================%RESET%
echo %BLUE%Verify Migrations%RESET%
echo %BLUE%========================================%RESET%
call php artisan migrate:status
goto end

:verify
echo %BLUE%Verify Database Integrity%RESET%
call php artisan db:show
goto end

:help
cls
echo.
echo %BLUE%Local Migration Testing Script%RESET%
echo.
echo Usage:
echo   test-migrations-local.bat [option]
echo.
echo Options:
echo   fresh       - Rollback ALL migrations and run fresh
echo   incremental - Run only pending migrations
echo   status      - Check migration status
echo   verify      - Verify database integrity
echo   help        - Show this help message
echo.
echo Examples:
echo   test-migrations-local.bat incremental
echo   test-migrations-local.bat fresh
echo   test-migrations-local.bat status
echo.
echo Notes:
echo   - Ensure you are in the project root directory
echo   - Ensure MySQL/database is running
echo   - Environment variables (.env) must be configured
echo   - Test fresh migrations first in local environment
echo.
pause
goto end

:unknown
echo %RED%Unknown option: %option%%RESET%
echo Run "test-migrations-local.bat help" for usage information
pause
exit /b 1

:end
echo.
pause
