# Build ZIP del plugin Welow Concesionarios
# Uso: .\build-zip.ps1

$ErrorActionPreference = 'Stop'

$pluginDir = Join-Path $PSScriptRoot 'welow-concesionarios'
$zipPath   = Join-Path $PSScriptRoot 'welow-concesionarios.zip'

# Leer versión desde el archivo principal del plugin
$mainFile = Join-Path $pluginDir 'welow-concesionarios.php'
$versionMatch = Select-String -Path $mainFile -Pattern '^\s*\*\s*Version:\s*([\d\.]+)' -AllMatches | Select-Object -First 1
if ($versionMatch) {
    $version = $versionMatch.Matches[0].Groups[1].Value
    Write-Host "Versión detectada: v$version" -ForegroundColor Cyan
} else {
    $version = 'unknown'
    Write-Host "No se pudo detectar la versión." -ForegroundColor Yellow
}

# Eliminar zip anterior si existe
if (Test-Path $zipPath) {
    Remove-Item $zipPath -Force
    Write-Host "ZIP anterior eliminado." -ForegroundColor DarkGray
}

# Crear nuevo zip
Write-Host "Generando ZIP..." -ForegroundColor Cyan
Compress-Archive -Path $pluginDir -DestinationPath $zipPath -CompressionLevel Optimal

$size = [math]::Round((Get-Item $zipPath).Length / 1KB, 2)
Write-Host ""
Write-Host "ZIP creado: welow-concesionarios.zip (v$version, $size KB)" -ForegroundColor Green
Write-Host "Listo para subir a WordPress." -ForegroundColor Green
