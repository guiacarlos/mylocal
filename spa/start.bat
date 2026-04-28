@echo off
setlocal
cd /d "%~dp0"

if not exist node_modules (
    echo [start] Installing dependencies...
    call npm install
    if errorlevel 1 (
        echo [start] npm install failed.
        exit /b 1
    )
)

echo [start] Launching Vite dev server on http://localhost:5173
call npm run dev
endlocal
