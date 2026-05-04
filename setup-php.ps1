# setup-php.ps1 — Configura PHP para que MyLocal pueda hablar con Gemini.
# Habilita las extensiones criticas (openssl, curl, fileinfo, gd, mbstring)
# en el php.ini del PHP detectado por la shell.
# Uso: .\setup-php.ps1

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

Write-Host "=== MyLocal - Setup de PHP ===" -ForegroundColor Cyan

# 1. Localizar el PHP activo
$phpBin = (Get-Command php -ErrorAction SilentlyContinue).Source
if (-not $phpBin) {
    Write-Host "ERROR: no se encontro 'php' en el PATH" -ForegroundColor Red
    exit 1
}
$phpDir = Split-Path -Parent $phpBin
Write-Host "PHP detectado: $phpBin" -ForegroundColor Gray

# 2. Verificar que existe php.ini-production al lado
$prodIni = Join-Path $phpDir "php.ini-production"
$phpIni  = Join-Path $phpDir "php.ini"
if (-not (Test-Path $prodIni)) {
    Write-Host "ERROR: no se encontro $prodIni (instalacion de PHP atipica)" -ForegroundColor Red
    exit 1
}

# 3. Copiar php.ini-production a php.ini si no existe ya un php.ini
if (Test-Path $phpIni) {
    Write-Host "php.ini ya existe, lo reuso." -ForegroundColor Gray
} else {
    Copy-Item $prodIni $phpIni -Force
    Write-Host "php.ini copiado desde php.ini-production." -ForegroundColor Green
}

# 4. Asegurar extension_dir y descomentar las extensiones criticas
$extensions = @('openssl', 'curl', 'fileinfo', 'gd', 'mbstring', 'intl')
$content = Get-Content $phpIni -Raw

# 4.1 extension_dir = "ext"
if ($content -notmatch '(?m)^\s*extension_dir\s*=\s*"ext"') {
    $content = $content -replace '(?m)^\s*;?\s*extension_dir\s*=\s*"ext"', 'extension_dir = "ext"'
}

# 4.2 descomentar cada extension
foreach ($ext in $extensions) {
    # busca ;extension=ext  o  extension=ext (con o sin espacios)
    $pattern = "(?m)^(\s*);(\s*extension\s*=\s*$ext\s*)$"
    if ($content -match $pattern) {
        $content = [regex]::Replace($content, $pattern, '$1$2')
        Write-Host "  habilitada: $ext" -ForegroundColor Green
    } elseif ($content -match "(?m)^\s*extension\s*=\s*$ext\s*$") {
        Write-Host "  ya habilitada: $ext" -ForegroundColor Gray
    } else {
        # no esta presente: anadir al final del bloque [PHP]
        $content += "`r`nextension=$ext`r`n"
        Write-Host "  anadida: $ext" -ForegroundColor Green
    }
}

# 5. Guardar
Set-Content -Path $phpIni -Value $content -Encoding ASCII
Write-Host ""
Write-Host "OK -> $phpIni actualizado" -ForegroundColor Green

# 6. Verificar
Write-Host ""
Write-Host "=== Verificacion ===" -ForegroundColor Cyan
$loaded = & php -r "echo php_ini_loaded_file();"
Write-Host "  php.ini cargado: $loaded"
Write-Host "  Extensiones criticas:"
$mods = & php -m
foreach ($ext in $extensions) {
    $hit = $mods | Where-Object { $_ -ieq $ext -or $_ -ieq "Core" -and $ext -eq "openssl" }
    $present = ($mods -join "`n") -match "(?im)^$ext$"
    $status = if ($present) { "OK" } else { "FALTA" }
    $color  = if ($present) { "Green" } else { "Red" }
    Write-Host "    $ext : $status" -ForegroundColor $color
}

Write-Host ""
Write-Host "Si todas las extensiones aparecen OK, MyLocal ya puede hablar con Gemini." -ForegroundColor Yellow
Write-Host "Si arrancaste run.bat antes, parar PHP (Ctrl+C en su ventana) y relanzar." -ForegroundColor Yellow
