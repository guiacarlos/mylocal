@echo off
chcp 65001 > nul
title MyLocal ? DESARROLLO (HMR)

echo.
echo  ---------------------------------------------------
echo  - MyLocal ? Entorno de Desarrollo Real            -
echo  - Frontend (Vite): http://localhost:5173         -
echo  - Backend (PHP):   http://localhost:8090         -
echo  ---------------------------------------------------
echo.
echo  [INFO] Los cambios en el c?digo se ver?n INSTANT?NEAMENTE.
echo  [INFO] No es necesario hacer 'build' hasta el final.
echo.

:: 1. Iniciar Backend PHP en segundo plano
echo  [1/2] Iniciando Backend PHP (Puerto 8090)...
start "MyLocal-Backend" /min php -S localhost:8090 router.php

:: 2. Iniciar Frontend Vite
echo  [2/2] Iniciando Frontend React (Puerto 5173)...
cd spa

:: Abrir el navegador autom?ticamente
start "" http://localhost:5173

:: Ejecutar Vite
npm run dev
