# =============================================================================
# Sync Module Deployment Script (Windows/PowerShell)
# Run this on the EXTERNAL server (207.231.110.79)
# =============================================================================

$ErrorActionPreference = "Stop"
$ProjectDir = Get-Location

Write-Host "=============================================" -ForegroundColor Cyan
Write-Host "  Sync Module Deployment" -ForegroundColor Cyan
Write-Host "=============================================" -ForegroundColor Cyan

# Step 1: Create module directories
Write-Host ""
Write-Host "[1/6] Creating Sync module directories..." -ForegroundColor Yellow
$dirs = @(
    "$ProjectDir\Modules\Sync\Controllers",
    "$ProjectDir\Modules\Sync\Routes",
    "$ProjectDir\Modules\Sync\Providers"
)
foreach ($dir in $dirs) {
    New-Item -ItemType Directory -Force -Path $dir | Out-Null
}
Write-Host "  Done" -ForegroundColor Green

# Step 2: Copy module files (you need to copy these files manually)
Write-Host "[2/6] Copy Sync module files to: $ProjectDir\Modules\Sync\" -ForegroundColor Yellow
Write-Host "  Required files:" -ForegroundColor Gray
Write-Host "    Modules\Sync\module.json" -ForegroundColor Gray
Write-Host "    Modules\Sync\Controllers\SyncController.php" -ForegroundColor Gray
Write-Host "    Modules\Sync\Routes\api.php" -ForegroundColor Gray
Write-Host "    Modules\Sync\Providers\ModuleServiceProvider.php" -ForegroundColor Gray

# Step 3: Update composer.json
Write-Host "[3/6] Updating composer.json autoload..." -ForegroundColor Yellow
$composerJson = Get-Content "$ProjectDir\composer.json" -Raw
if ($composerJson -notmatch 'App\\Modules') {
    $composerJson = $composerJson -replace '"App\\": "app/"', '"App\\Modules\\": "Modules/",`n            "App\\": "app/"'
    Set-Content "$ProjectDir\composer.json" $composerJson
    Write-Host "  Added App\Modules namespace" -ForegroundColor Green
} else {
    Write-Host "  Already configured" -ForegroundColor Green
}

# Step 4: Update bootstrap/providers.php
Write-Host "[4/6] Registering ModuleServiceProvider..." -ForegroundColor Yellow
$providersFile = "$ProjectDir\bootstrap\providers.php"
$providers = Get-Content $providersFile -Raw
if ($providers -notmatch 'Sync.*ModuleServiceProvider') {
    $providers = $providers -replace '(Distribution.*ModuleServiceProvider.*\n)', "`$1    \App\Modules\Sync\Providers\ModuleServiceProvider::class,`n"
    Set-Content $providersFile $providers
    Write-Host "  Registered" -ForegroundColor Green
} else {
    Write-Host "  Already registered" -ForegroundColor Green
}

# Step 5: Add .env variables
Write-Host "[5/6] Adding .env variables..." -ForegroundColor Yellow
$envFile = "$ProjectDir\.env"
$envContent = Get-Content $envFile -Raw
if ($envContent -notmatch 'SYNC_EXTERNAL_URL') {
    Add-Content $envFile @"

# Sync Server-to-Server
SYNC_EXTERNAL_URL=http://207.231.110.79
SYNC_TOKEN=REPLACE_WITH_YOUR_SECRET_TOKEN
"@
    Write-Host "  Added" -ForegroundColor Green
    Write-Host "  WARNING: Edit .env and set SYNC_TOKEN!" -ForegroundColor Red
} else {
    Write-Host "  Already configured" -ForegroundColor Green
}

# Step 6: Regenerate autoload
Write-Host "[6/6] Regenerating autoload..." -ForegroundColor Yellow
Set-Location $ProjectDir
& composer dump-autoload
& php artisan config:clear
& php artisan route:clear

Write-Host ""
Write-Host "=============================================" -ForegroundColor Cyan
Write-Host "  Deployment Complete!" -ForegroundColor Green
Write-Host "=============================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "  1. Edit .env: Set SYNC_TOKEN (must match local server)"
Write-Host "  2. Verify: php artisan route:list | Select-String v2/sync"
