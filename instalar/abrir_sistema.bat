@echo off
setlocal

:: ==================================================
:: IntelliCarnic - Iniciador Inteligente v3
:: Verifica si los servicios de servidor web (Apache / Nginx) están activos.
:: Si los servicios están corriendo -> abre el sistema de inmediato.
:: Si están detenidos -> inicia Laragon y abre el sistema.
:: ==================================================

set SISTEMA_URL=http://intellicarnic.test
set LARAGON_EXE=C:\laragon\laragon.exe

:: ---- Verificar si Apache (httpd.exe) o Nginx (nginx.exe) están activos ----
set SERVIDOR_ACTIVO=0

tasklist /FI "IMAGENAME eq httpd.exe" 2>NUL | find /I "httpd.exe" >NUL
if %ERRORLEVEL% EQU 0 set SERVIDOR_ACTIVO=1

tasklist /FI "IMAGENAME eq nginx.exe" 2>NUL | find /I "nginx.exe" >NUL
if %ERRORLEVEL% EQU 0 set SERVIDOR_ACTIVO=1

if %SERVIDOR_ACTIVO% EQU 1 (
    :: CASO NORMAL: Servidor web activo -> Abrir navegador de inmediato
    start "" "%SISTEMA_URL%"
) else (
    :: CASO DE RECUPERACIÓN: Servidor apagado -> Iniciar Laragon
    if exist "%LARAGON_EXE%" (
        start "" "%LARAGON_EXE%"
        :: Esperar 3 segundos para inicialización
        timeout /t 3 /nobreak >NUL
        start "" "%SISTEMA_URL%"
    ) else (
        echo ERROR: No se encontró Laragon en %LARAGON_EXE%
        pause
    )
)

endlocal
