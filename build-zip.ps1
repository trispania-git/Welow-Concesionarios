# Build ZIP del plugin Welow Concesionarios
# Uso: .\build-zip.ps1
#
# IMPORTANTE: NO usar Compress-Archive en PowerShell 5.1, genera ZIPs con
# separadores '\' (backslash) que WordPress en Linux interpreta como
# nombres de archivo literales y rompe la estructura del plugin.
# Usamos .NET ZipArchive directamente con '/' (forward slash).

$ErrorActionPreference = 'Stop'

$pluginDir = Join-Path $PSScriptRoot 'welow-concesionarios'
$zipPath   = Join-Path $PSScriptRoot 'welow-concesionarios.zip'

# Leer versión
$mainFile = Join-Path $pluginDir 'welow-concesionarios.php'
$versionMatch = Select-String -Path $mainFile -Pattern '^\s*\*\s*Version:\s*([\d\.]+)' -AllMatches | Select-Object -First 1
if ($versionMatch) {
    $version = $versionMatch.Matches[0].Groups[1].Value
    Write-Host "Versión detectada: v$version" -ForegroundColor Cyan
} else {
    $version = 'unknown'
}

# Eliminar zip anterior
if (Test-Path $zipPath) {
    Remove-Item $zipPath -Force
}

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

Write-Host "Generando ZIP (con forward slashes)..." -ForegroundColor Cyan

$fs = [System.IO.File]::Create($zipPath)
$zip = New-Object System.IO.Compression.ZipArchive($fs, [System.IO.Compression.ZipArchiveMode]::Create)

$base = (Resolve-Path $pluginDir).Path
$parent = Split-Path $base -Parent
$files = Get-ChildItem -Path $pluginDir -Recurse -File

foreach ($f in $files) {
    $rel = $f.FullName.Substring($parent.Length + 1) -replace '\\', '/'
    $entry = $zip.CreateEntry($rel, [System.IO.Compression.CompressionLevel]::Optimal)
    $es = $entry.Open()
    $fsr = [System.IO.File]::OpenRead($f.FullName)
    $fsr.CopyTo($es)
    $fsr.Close()
    $es.Close()
}

$zip.Dispose()
$fs.Close()

$size = [math]::Round((Get-Item $zipPath).Length / 1KB, 2)
Write-Host ""
Write-Host "ZIP creado: welow-concesionarios.zip (v$version, $size KB)" -ForegroundColor Green
Write-Host "Listo para subir a WordPress." -ForegroundColor Green
