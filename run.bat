@echo off
title MyLocal - DESARROLLO

:: Uso: run.bat [hosteleria|clinica|...]  (por defecto: hosteleria)
set TEMPLATE=%~1
if "%TEMPLATE%"=="" set TEMPLATE=hosteleria

:: Puerto por template
if "%TEMPLATE%"=="clinica" (
    set FRONTEND_PORT=5174
) else (
    set FRONTEND_PORT=5173
)

echo.
echo  ================================================
echo  MyLocal - Entorno de Desarrollo (HMR activo)
echo  Template:        %TEMPLATE%
echo  Frontend (Vite): http://localhost:%FRONTEND_PORT%
echo  Backend  (PHP):  http://localhost:8091
echo  ================================================
echo.
echo  Los cambios en el codigo se ven INSTANTANEAMENTE.
echo  No es necesario hacer build hasta el final.
echo.

:: Liberar puertos que un ciclo anterior haya dejado ocupados.
:: Solo mata procesos LISTEN en 8091/8766/8767/5173/5174 — selectivo, no por nombre.
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0tools\dev\free-ports.ps1" -Quiet

:: 1. Backend PHP en segundo plano (para llamadas API /acide/...)
echo  [1/2] Iniciando Backend PHP en puerto 8091...
start "MyLocal-Backend" /min cmd /c "cd /d %~dp0 && php -S 127.0.0.1:8091 -t . router.php"

:: Esperar 1 segundo para que PHP inicie
timeout /t 1 /nobreak > nul

:: 2. Frontend Vite (abre el navegador automaticamente)
echo  [2/2] Iniciando Frontend React en puerto %FRONTEND_PORT%...
set TEMPLATE_DIR=%~dp0templates\%TEMPLATE%
if exist "%TEMPLATE_DIR%\" (
    cd /d "%TEMPLATE_DIR%"
    call pnpm dev
) else (
    cd /d %~dp0spa
    call npm run dev
)
