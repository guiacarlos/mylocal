@echo off
chcp 65001 > nul
title MyLocal ? DESARROLLO (HMR)

echo.
echo  ---------------------------------------------------
echo  - MyLocal ? Entorno de Desarrollo Real            -
echo  - Frontend (Vite): http://localhost:5173         -
echo  Backend  (PHP):  http://localhost:8091
echo  ================================================
echo.
echo  Los cambios en el codigo se ven INSTANTANEAMENTE.
echo.

:: Matar procesos anteriores si existen
taskkill /F /IM php.exe /T > nul 2>&1

:: 1. Backend PHP en segundo plano
echo  [1/3] Iniciando Backend PHP en puerto 8091...
start "MyLocal-Backend" /min cmd /c "cd /d %~dp0 && php -S 127.0.0.1:8091 -t . router.php"

:: 2. Iniciar Frontend Vite
echo  [2/2] Iniciando Frontend React (Puerto 5173)...
cd spa

:: Abrir el navegador autom?ticamente
start "" http://localhost:5173

:: Ejecutar Vite
npm run dev
