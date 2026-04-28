@echo off
chcp 65001 > nul
title MyLocal — Servidor de desarrollo

echo.
echo  ╔══════════════════════════════════════════╗
echo  ║         MyLocal — Dev Server             ║
echo  ║   http://localhost:3000                  ║
echo  ╚══════════════════════════════════════════╝
echo.
echo  Sirviendo: release\
echo  Puerto:    3000
echo.
echo  Flujo de trabajo:
echo    1. Edita en spa\src\
echo    2. Ejecuta build.ps1 en otra ventana
echo    3. Recarga el navegador
echo.

if not exist "release\index.html" (
    echo  [ERROR] release\index.html no encontrado.
    echo  Ejecuta primero: .\build.ps1
    echo.
    pause
    exit /b 1
)

where php > nul 2>&1
if errorlevel 1 (
    echo  [ERROR] PHP no encontrado en el PATH.
    pause
    exit /b 1
)

start "" /min cmd /c "timeout /t 1 /nobreak > nul && start http://localhost:3000"

php -S localhost:3000 -t release release\router.php
