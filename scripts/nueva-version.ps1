<#
    Congela el estado actual del prototipo final como una versión más del
    histórico navegable.

    ORDEN DE USO — importa:

        1. Haces el cambio de diseño en prototipos/v3-final/index.html
        2. Lo commiteas
        3. Ejecutas este script
        4. Commiteas la versión nueva

    El paso 3 va después del 2 porque el script guarda el hash de HEAD, y así
    ese hash apunta al commit que produjo justo ese diseño. Si lo ejecutas
    antes de commitear, la versión quedará etiquetada con el commit anterior.

    Uso:
        .\scripts\nueva-version.ps1 "Título corto de lo que cambió"
#>
param(
    [Parameter(Mandatory = $true)]
    [string]$Titulo
)

$ErrorActionPreference = 'Stop'

$raiz      = Split-Path -Parent $PSScriptRoot
$carpeta   = Join-Path $raiz 'prototipos\v3-final'
$indice    = Join-Path $carpeta 'index.html'
$jsonPath  = Join-Path $carpeta 'versiones.json'
$versiones = Join-Path $carpeta 'versiones'

if (-not (Test-Path $indice))   { throw "No encuentro $indice" }
if (-not (Test-Path $jsonPath)) { throw "No encuentro $jsonPath" }
if (-not (Test-Path $versiones)) { New-Item -ItemType Directory -Path $versiones | Out-Null }

$datos = Get-Content $jsonPath -Raw -Encoding UTF8 | ConvertFrom-Json
$n     = 1 + ($datos.versiones | Measure-Object -Property n -Maximum).Maximum
$nombre = "v$n.html"
$destino = Join-Path $versiones $nombre

if (Test-Path $destino) { throw "$nombre ya existe. Revisa versiones.json." }

# Copia byte a byte: la instantánea tiene que ser exactamente lo que se publicó.
[System.IO.File]::WriteAllBytes($destino, [System.IO.File]::ReadAllBytes($indice))

$hash = (& git -C $raiz rev-parse --short HEAD).Trim()

$entrada = [ordered]@{
    n       = $n
    archivo = $nombre
    fecha   = (Get-Date -Format 'yyyy-MM-dd')
    commit  = $hash
    titulo  = $Titulo
}

$salida = [ordered]@{
    actual    = $Titulo
    versiones = @($datos.versiones) + $entrada
}

# Sin BOM: el archivo lo lee fetch() desde el navegador y un BOM lo rompe.
$texto = ($salida | ConvertTo-Json -Depth 5) + "`n"
[System.IO.File]::WriteAllText($jsonPath, $texto, (New-Object System.Text.UTF8Encoding($false)))

Write-Host "Versión $n creada: prototipos/v3-final/versiones/$nombre"
Write-Host "  commit : $hash"
Write-Host "  título : $Titulo"
Write-Host ""
Write-Host "Ahora commitea:  git add prototipos/v3-final/versiones.json prototipos/v3-final/versiones/$nombre"
