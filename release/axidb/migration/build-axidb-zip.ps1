# AxiDB - empaqueta axidb/ en un zip distribuible (Fase 5).
#
# Uso: powershell -ExecutionPolicy Bypass -File axidb/migration/build-axidb-zip.ps1 [version]
#      o desde Git Bash:  pwsh axidb/migration/build-axidb-zip.ps1 v1.0
#
# Genera: axidb-<version>.zip en el directorio actual.
# Excluye: tests/_tmp_*, docs/api (re-generables), runtime data.

param(
    [string]$Version = "v1.0-dev"
)

$ErrorActionPreference = 'Stop'
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$RepoRoot  = Resolve-Path "$ScriptDir\..\.." | Select-Object -ExpandProperty Path
$Out       = "axidb-$Version.zip"

Set-Location $RepoRoot

if (-not (Test-Path "axidb")) {
    Write-Error "'axidb/' no existe en $RepoRoot"
    exit 2
}

Write-Host "Empaquetando axidb/ -> $Out (version: $Version)"
Write-Host ""

if (Test-Path $Out) { Remove-Item $Out }

# PowerShell 5+ tiene Compress-Archive. Excluimos via copia previa a temp.
$Tmp = New-Item -ItemType Directory -Path (Join-Path $env:TEMP "axidb-pkg-$(Get-Random)") -Force
try {
    Copy-Item -Recurse -Path "axidb" -Destination $Tmp.FullName

    # Limpiar excluidos.
    Get-ChildItem -Path "$($Tmp.FullName)\axidb\tests" -Directory `
        -Filter "_tmp*" -ErrorAction SilentlyContinue |
        Remove-Item -Recurse -Force -ErrorAction SilentlyContinue

    if (Test-Path "$($Tmp.FullName)\axidb\docs\api") {
        Remove-Item -Recurse -Force "$($Tmp.FullName)\axidb\docs\api"
    }
    if (Test-Path "$($Tmp.FullName)\axidb\web\config.json") {
        # Se queda fuera del zip para que el hosting destino arranque con default seguro.
        Remove-Item -Force "$($Tmp.FullName)\axidb\web\config.json"
    }

    # README en la raiz del zip.
    $Readme = @"
AxiDB $Version

Estructura del paquete:
  axidb/
    axi.php           - punto de entrada embebido
    api/axi.php       - gateway HTTP
    engine/           - motor (Op model + Storage + Vault + Backup)
    sdk/php/          - cliente PHP (Embedded + HTTP transports)
    sql/              - compilador AxiSQL
    cli/, bin/        - terminal
    web/              - dashboard vanilla (config.json se crea con default seguro)
    examples/         - notas, portfolio, remote-client, hello.php
    docs/standard/    - specs formales

Instalar:
  1. Expand-Archive $Out
  2. Sube axidb/ a tu hosting PHP.
  3. require 'axidb/axi.php' y empieza a usar.

Mas info: axidb/docs/guide/01-quickstart.md
"@
    Set-Content -Path "$($Tmp.FullName)\README.txt" -Value $Readme -Encoding UTF8

    Compress-Archive -Path "$($Tmp.FullName)\*" -DestinationPath $Out -CompressionLevel Optimal
} finally {
    Remove-Item -Recurse -Force $Tmp.FullName
}

Write-Host ""
Write-Host "Hecho:"
Get-Item $Out | Format-Table Name, Length -AutoSize
Write-Host ""
Write-Host "Para subir a un hosting:"
Write-Host "  1. Expand-Archive $Out -DestinationPath ."
Write-Host "  2. Sube /axidb a la raiz del hosting o adyacente a tu app."
Write-Host "  3. require 'axidb/axi.php' desde tu codigo PHP."
