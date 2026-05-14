# ╔══════════════════════════════════════════════════════════════════╗
# ║ MYLOCAL AUTH LOCK - load-bearing                                 ║
# ║ Build pipeline. DEBE copiar spa/server, materializar configs y   ║
# ║ ejecutar test_login.php (gate). Si falla, exit 1.                ║
# ║ Antes de modificar, leer claude/AUTH_LOCK.md.                    ║
# ╚══════════════════════════════════════════════════════════════════╝
# build.ps1 — MyLocal
# Genera /release/ completa y autosuficiente para subir a cualquier servidor Apache+PHP.
# Uso: .\build.ps1 [--template=hosteleria|clinica|...]
# No requiere nada instalado en el servidor destino.

param(
    [string]$Template = "hosteleria"
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

$ROOT    = $PSScriptRoot
$SPA     = Join-Path $ROOT "spa"
$RELEASE = Join-Path $ROOT "release"

Write-Host "=== MyLocal Build (template: $Template) ===" -ForegroundColor Cyan

# 0. Liberar puertos que un build/test anterior haya dejado ocupados.
# El test gate (paso 2.3) lanza su propio PHP server en 8766; si un build
# previo no termino limpio, el puerto sigue listening y el siguiente test
# se cuelga indefinidamente esperando a su servidor.
& (Join-Path $ROOT "tools\dev\free-ports.ps1") -Quiet

# 1. Compilar SPA React
Write-Host "[1/3] Compilando template '$Template'..." -ForegroundColor Yellow

$templateDir = Join-Path $ROOT "templates\$Template"
if (Test-Path $templateDir) {
    # Nuevo sistema: templates/ con pnpm workspaces
    try {
        cmd /c "pnpm -F $Template build"
    } catch {
        Write-Host "ERROR: pnpm build fallo para template '$Template'" -ForegroundColor Red
        exit 1
    }
} else {
    # Fallback legacy: spa/ con npm
    Set-Location $SPA
    try {
        cmd /c "npm run build --silent"
    } catch {
        Write-Host "ERROR: El proceso de build falló" -ForegroundColor Red
        Set-Location $ROOT
        exit 1
    }
    Set-Location $ROOT
}

if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: build fallo (exit $LASTEXITCODE)" -ForegroundColor Red
    exit 1
}
Write-Host "      OK -> release/ generada (JS + CSS)" -ForegroundColor Green

# 2. Copiar backend PHP y archivos de servidor
Write-Host "[2/3] Copiando backend PHP y assets..." -ForegroundColor Yellow

$include = @(
    "CORE",
    "CAPABILITIES",
    "axidb",
    "fonts",
    "MEDIA",
    "seed",
    ".htaccess",
    "gateway.php",
    "router.php",
    "favicon.png",
    "favicon.jpg",
    "spa\server",
    "manifest.json",
    "robots.txt",
    "schema.json"
)

foreach ($item in $include) {
    $src = Join-Path $ROOT $item
    $dst = Join-Path $RELEASE $item
    if (Test-Path $src) {
        if ((Get-Item $src).PSIsContainer) {
            # robocopy /E (sin /MIR) copia todo y crea subdirs nuevos
            # sin borrar nada en destino. Crucial: data/ con users
            # bootstrapeados se conserva.
            robocopy $src $dst /E /NFL /NDL /NJH /NJS /NC /NS /NP > $null
            # robocopy: 0-7 OK, 8+ es fallo. ResetEEXITCODE despues.
            if ($LASTEXITCODE -ge 8) {
                Write-Host "ERROR: robocopy fallo para $item (exit $LASTEXITCODE)" -ForegroundColor Red
                exit 1
            }
            $global:LASTEXITCODE = 0
        } else {
            Copy-Item -Path $src -Destination $dst -Force
        }
        Write-Host "      $item" -ForegroundColor Gray
    }
}

# STORAGE/ se excluye: son datos en tiempo real del restaurante (no van en release)
# spa/    se excluye: es solo fuente de desarrollo
# .git/   se excluye: control de versiones, no para el servidor
# claude/ se excluye: documentacion interna

Write-Host "      OK -> backend copiado" -ForegroundColor Green

# 2.1 Limpieza de archivos de debug del CORE legacy.
# IMPORTANTE: el patron NO incluye "test_*.php" porque eso borraria
# spa/server/tests/test_login.php que es el gate del paso 2.3.
Write-Host "      Limpiando archivos de debug..." -ForegroundColor Gray
Get-ChildItem -Path $RELEASE -Include "debug_*.php", "diag.php", "*.log" -Recurse |
    Where-Object { $_.FullName -notmatch '\\tests\\' } |
    Remove-Item -Force -ErrorAction SilentlyContinue

# 2.2 Materializar configs spa/server: cada *.json.example sin .json equivalente
# se copia a .json. Asi el server arranca con valores por defecto y no falla
# por config inexistente. Los secretos reales se editan tras el despliegue.
Write-Host "      Materializando configs spa/server..." -ForegroundColor Gray
$configDir = Join-Path $RELEASE "spa\server\config"
if (Test-Path $configDir) {
    Get-ChildItem -Path $configDir -Filter "*.json.example" | ForEach-Object {
        $real = $_.FullName -replace '\.example$', ''
        if (-not (Test-Path $real)) {
            Copy-Item -Path $_.FullName -Destination $real -Force
            Write-Host "        $($_.Name) -> $(Split-Path $real -Leaf)" -ForegroundColor DarkGray
        }
    }
}

# 2.3 GATE DE LOGIN: ejecutar el test de integracion contra el release/.
# Si CUALQUIER assertion falla, la build aborta. Esto impide que regresiones
# en el flujo de auth pasen a produccion. Ver claude/AUTH_LOCK.md.
Write-Host "      Ejecutando test de integracion del login..." -ForegroundColor Gray
$testScript = Join-Path $RELEASE "spa\server\tests\test_login.php"
if (Test-Path $testScript) {
    $testOut = & php $testScript "--root=$RELEASE" "--port=8766" 2>&1
    $testExit = $LASTEXITCODE
    if ($testExit -ne 0) {
        Write-Host ""
        Write-Host "ERROR: el test de login fallo. La build NO continua." -ForegroundColor Red
        Write-Host "Salida del test:" -ForegroundColor Yellow
        $testOut | ForEach-Object { Write-Host "  $_" }
        Write-Host ""
        Write-Host "Lee claude/AUTH_LOCK.md y arregla la regresion antes de re-buildear." -ForegroundColor Yellow
        & (Join-Path $ROOT "tools\dev\free-ports.ps1") -Ports 8766 -Quiet
        exit 1
    }
    Write-Host "      OK -> tests de login + OCR pasan" -ForegroundColor Green
    # Doble seguro: si el cleanup del test dejo el server PHP huerfano en 8766
    # (sucede de vez en cuando en Windows con PHP built-in server), lo matamos
    # aqui antes de seguir. free-ports es idempotente; si no hay nada, no hace nada.
    & (Join-Path $ROOT "tools\dev\free-ports.ps1") -Ports 8766 -Quiet
} else {
    Write-Host "AVISO: test_login.php no encontrado en release. Saltando gate." -ForegroundColor Yellow
}

# 3. Crear STORAGE/ vacio con .gitkeep para que Apache pueda escribir datos
Write-Host "[3/3] Preparando estructura de datos vacia..." -ForegroundColor Yellow
$storageRelease = Join-Path $RELEASE "STORAGE"
if (-not (Test-Path $storageRelease)) {
    New-Item -ItemType Directory -Path $storageRelease | Out-Null
}
$gitkeep = Join-Path $storageRelease ".gitkeep"
if (-not (Test-Path $gitkeep)) {
    "" | Out-File -FilePath $gitkeep -Encoding utf8
}
Write-Host "      OK -> STORAGE/ vacio listo" -ForegroundColor Green

# Resumen
$releaseSize = (Get-ChildItem $RELEASE -Recurse -File | Measure-Object -Property Length -Sum).Sum
$sizeMB = [math]::Round($releaseSize / 1MB, 2)

Write-Host ""
Write-Host "=== BUILD COMPLETADO ===" -ForegroundColor Cyan
Write-Host "Carpeta: $RELEASE" -ForegroundColor White
Write-Host "Tamano:  $sizeMB MB" -ForegroundColor White
Write-Host ""
Write-Host "Para desplegar: sube el contenido de release/ al servidor." -ForegroundColor Yellow
Write-Host "No se necesita instalar nada. Requiere Apache + PHP >= 7.4." -ForegroundColor Yellow
