@echo off
setlocal enableextensions enabledelayedexpansion

:: ==================================================
:: IntelliCarnic - Script de Actualización Automática
:: Descarga la última versión de GitHub, ejecuta las
:: migraciones de base de datos y optimiza la caché.
:: ==================================================

echo ==================================================
echo       INTELLICARNIC - ACTUALIZACION DE SISTEMA
echo ==================================================
echo.

:: Definir ruta del proyecto (directorio padre de instalar/)
set "PROJECT_DIR=%~dp0.."
cd /d "%PROJECT_DIR%"

set GIT_TERMINAL_PROMPT=0

echo [1/3] Descargando ultimas mejoras desde GitHub...
git pull origin main
if %ERRORLEVEL% NEQ 0 (
    echo.
    echo ERROR: No se pudo conectar con GitHub para descargar cambios.
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
echo   ¡SISTEMA ACTUALIZADO Y OPTIMIZADO CON EXITO!
echo ==================================================
echo.
timeout /t 3 /nobreak >NUL
endlocal
