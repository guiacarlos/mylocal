@echo off
title MyLocal - DESARROLLO

echo.
echo  ================================================
echo  MyLocal - Entorno de Desarrollo (HMR activo)
echo  Frontend (Vite): http://localhost:5173
echo  Backend  (PHP):  http://localhost:8090
echo  ================================================
echo.
echo  Los cambios en el codigo se ven INSTANTANEAMENTE.
echo  No es necesario hacer build hasta el final.
echo.

:: Matar procesos anteriores si existen
taskkill /F /IM php.exe /T > nul 2>&1

:: 1. Backend PHP en segundo plano (para llamadas API /acide/...)
echo  [1/2] Iniciando Backend PHP en puerto 8090...
start "MyLocal-Backend" /min cmd /c "cd /d %~dp0 && php -S 127.0.0.1:8090 -t . router.php"

:: Esperar 1 segundo para que PHP inicie
timeout /t 1 /nobreak > nul

:: 2. Frontend Vite (abre el navegador automaticamente)
echo  [2/2] Iniciando Frontend React en puerto 5173...
cd /d %~dp0spa
npm run dev
