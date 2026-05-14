# tools/dev/free-ports.ps1 — MyLocal
#
# Libera puertos que el ciclo de desarrollo/build deja ocupados cuando un
# proceso anterior no termino limpio. Sintomas que arregla:
#   - "Address already in use" al lanzar run.bat / build.ps1
#   - test_login.php colgado porque su servidor PHP previo sigue escuchando
#   - Vite imposible de arrancar en 5173
#
# Filosofia: solo mata procesos que tengan el puerto en LISTEN. No toca
# nada mas. Si un puerto esta libre, no hace nada. Es seguro re-ejecutar.
#
# Uso:
#   .\tools\dev\free-ports.ps1                 -> libera la lista por defecto
#   .\tools\dev\free-ports.ps1 -Ports 8091,5173 -> libera solo los indicados
#   .\tools\dev\free-ports.ps1 -Quiet           -> sin output salvo errores

[CmdletBinding()]
param(
    [int[]] $Ports = @(8091, 8766, 8767, 5173),
    [switch] $Quiet
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

function Write-Info {
    param([string] $Message, [string] $Color = 'Gray')
    if (-not $Quiet) {
        Write-Host $Message -ForegroundColor $Color
    }
}

function Stop-PortOwner {
    param([int] $Port)

    $owners = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue |
        Select-Object -ExpandProperty OwningProcess -Unique

    if (-not $owners) {
        Write-Info "  $Port -> libre" 'DarkGray'
        return $true
    }

    foreach ($ownerPid in $owners) {
        try {
            $proc = Get-Process -Id $ownerPid -ErrorAction Stop
            $name = $proc.ProcessName
            Stop-Process -Id $ownerPid -Force -ErrorAction Stop
            Write-Info "  $Port -> mato $name (PID $ownerPid)" 'Yellow'
        } catch {
            Write-Info "  $Port -> no se pudo matar PID ${ownerPid}: $($_.Exception.Message)" 'Red'
        }
    }

    Start-Sleep -Milliseconds 300
    $still = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue
    if ($still) {
        Write-Info "  $Port -> SIGUE OCUPADO tras intento de liberacion" 'Red'
        return $false
    }
    return $true
}

Write-Info "[free-ports] Revisando $($Ports -join ', ')..." 'Cyan'

$failed = @()
foreach ($p in $Ports) {
    $ok = Stop-PortOwner -Port $p
    if (-not $ok) { $failed += $p }
}

if ($failed.Count -gt 0) {
    Write-Info "[free-ports] FALLO: puertos aun ocupados: $($failed -join ', ')" 'Red'
    exit 1
}

Write-Info "[free-ports] OK" 'Green'
exit 0
