@echo off
setlocal enableextensions enabledelayedexpansion

:: ==================================================
:: Script Universal de Actualizacion Automatica (Laravel)
:: Descarga la ultima version de Git, ejecuta migraciones
:: de base de datos y optimiza la cache del sistema.
:: ==================================================

set "PROJECT_DIR=%~dp0.."
cd /d "%PROJECT_DIR%"

set "ENV_FILE=%PROJECT_DIR%\.env"
set "APP_NAME="

if exist "%ENV_FILE%" (
    for /f "usebackq tokens=1,* delims==" %%A in ("%ENV_FILE%") do (
        set "KEY=%%A"
        for /f "tokens=* delims= " %%K in ("!KEY!") do set "KEY=%%K"
        if /i "!KEY!"=="APP_NAME" (
            set "VAL=%%B"
            set "VAL=!VAL:"=!"
            set "VAL=!VAL:'=!"
            for /f "tokens=* delims= " %%V in ("!VAL!") do set "APP_NAME=%%V"
        )
    )
)

if "%APP_NAME%"=="" set "APP_NAME=SISTEMA"
if /i "%APP_NAME%"=="laravel" (
    for %%F in ("%PROJECT_DIR%") do set "APP_NAME=%%~nxF"
)

echo ==================================================
echo       !APP_NAME! - ACTUALIZACION AUTOMATICA
echo ==================================================
echo.

set GIT_TERMINAL_PROMPT=0

:: Detectar rama actual de git
set "CURRENT_BRANCH=main"
for /f "delims=" %%B in ('git branch --show-current 2^>NUL') do (
    if not "%%B"=="" set "CURRENT_BRANCH=%%B"
)

echo [1/3] Descargando ultimas mejoras desde Git (rama: %CURRENT_BRANCH%)...
git pull origin %CURRENT_BRANCH%
if %ERRORLEVEL% NEQ 0 (
    echo.
    echo ERROR: No se pudo conectar con el repositorio para descargar cambios.
    echo Verifica tu conexion a internet.
    pause
    exit /b 1
)

echo.
echo [2/3] Aplicando migraciones de base de datos...
php artisan migrate --force

echo.
echo [3/3] Optimizando configuracion y vistas...
php artisan config:cache
php artisan view:cache
php artisan route:clear

echo.
echo ==================================================
echo   SISTEMA ACTUALIZADO Y OPTIMIZADO CON EXITO!
echo ==================================================
echo.
timeout /t 3 /nobreak >NUL
endlocal
