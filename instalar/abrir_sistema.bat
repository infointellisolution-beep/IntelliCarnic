@echo off
setlocal enableextensions enabledelayedexpansion

:: ==================================================
:: Lanzador Inteligente Universal para Laravel / Laragon
:: Lee la URL automaticamente desde el archivo .env
:: ==================================================

set "PROJECT_DIR=%~dp0.."
set "ENV_FILE=%PROJECT_DIR%\.env"
set "SISTEMA_URL="
set "LARAGON_EXE=C:\laragon\laragon.exe"

:: Leer APP_URL del archivo .env si existe
if exist "%ENV_FILE%" (
    for /f "usebackq eol=# tokens=1,2 delims==" %%A in ("%ENV_FILE%") do (
        set "KEY=%%A"
        if /i "!KEY!"=="APP_URL" (
            set "SISTEMA_URL=%%B"
            set "SISTEMA_URL=!SISTEMA_URL:"=!"
            set "SISTEMA_URL=!SISTEMA_URL:'=!"
        )
    )
)

:: Fallback si no se encontro APP_URL en .env
if "%SISTEMA_URL%"=="" (
    for %%F in ("%PROJECT_DIR%") do set "DIR_NAME=%%~nxF"
    set "SISTEMA_URL=http://!DIR_NAME!.test"
)

:: ---- Verificar si Apache (httpd.exe) o Nginx (nginx.exe) estan activos ----
set SERVIDOR_ACTIVO=0

tasklist /FI "IMAGENAME eq httpd.exe" 2>NUL | find /I "httpd.exe" >NUL
if %ERRORLEVEL% EQU 0 set SERVIDOR_ACTIVO=1

tasklist /FI "IMAGENAME eq nginx.exe" 2>NUL | find /I "nginx.exe" >NUL
if %ERRORLEVEL% EQU 0 set SERVIDOR_ACTIVO=1

if %SERVIDOR_ACTIVO% EQU 1 (
    :: CASO NORMAL: Servidor web activo -> Abrir navegador de inmediato
    start "" "%SISTEMA_URL%"
) else (
    :: CASO DE RECUPERACION: Servidor apagado -> Iniciar Laragon
    if exist "%LARAGON_EXE%" (
        start "" "%LARAGON_EXE%"
        :: Esperar 3 segundos para inicializacion de servicios
        timeout /t 3 /nobreak >NUL
        start "" "%SISTEMA_URL%"
    ) else (
        echo ERROR: No se encontro Laragon en %LARAGON_EXE%
        pause
    )
)

endlocal
