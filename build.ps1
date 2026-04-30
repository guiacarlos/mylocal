# build.ps1 — MyLocal
# Genera /release/ completa y autosuficiente para subir a cualquier servidor Apache+PHP.
# Uso: .\build.ps1
# No requiere nada instalado en el servidor destino.

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

$ROOT    = $PSScriptRoot
$SPA     = Join-Path $ROOT "spa"
$RELEASE = Join-Path $ROOT "release"

Write-Host "=== MyLocal Build ===" -ForegroundColor Cyan

# 1. Compilar SPA React
Write-Host "[1/3] Compilando SPA React..." -ForegroundColor Yellow
Set-Location $SPA
npm run build --silent
if ($LASTEXITCODE -ne 0) { Write-Host "ERROR: npm run build fallo" -ForegroundColor Red; exit 1 }
Set-Location $ROOT
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
    "manifest.json",
    "robots.txt",
    "schema.json"
)

foreach ($item in $include) {
    $src = Join-Path $ROOT $item
    $dst = Join-Path $RELEASE $item
    if (Test-Path $src) {
        if ((Get-Item $src).PSIsContainer) {
            Copy-Item -Path $src -Destination $dst -Recurse -Force
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

# 2.1 Limpieza de archivos de seguridad/debug
Write-Host "      Limpiando archivos de debug..." -ForegroundColor Gray
Get-ChildItem -Path $RELEASE -Include "debug_*.php", "diag.php", "test_*.php", "*.log" -Recurse | Remove-Item -Force -ErrorAction SilentlyContinue

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
