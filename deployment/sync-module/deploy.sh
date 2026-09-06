#!/bin/bash
# =============================================================================
# Sync Module Deployment Script
# Run this on the EXTERNAL server (207.231.110.79)
# =============================================================================

set -e

PROJECT_DIR=$(pwd)
MODULES_DIR="$PROJECT_DIR/Modules"

echo "============================================="
echo "  Sync Module Deployment"
echo "============================================="

# Step 1: Create Modules directory if not exists
echo ""
echo "[1/6] Creating Modules directory..."
mkdir -p "$MODULES_DIR/Sync/Controllers"
mkdir -p "$MODULES_DIR/Sync/Routes"
mkdir -p "$MODULES_DIR/Sync/Providers"

# Step 2: Copy Sync module files
echo "[2/6] Copying Sync module files..."
# (Files should be placed in Modules/Sync/ manually or via scp)

# Step 3: Update composer.json - add PSR-4 autoload
echo "[3/6] Updating composer.json autoload..."
if ! grep -q '"App\\\\Modules\\\\"' "$PROJECT_DIR/composer.json"; then
    # Add "App\\Modules\\": "Modules/" to autoload.psr-4
    sed -i 's|"App\\\\": "app/"|"App\\\\Modules\\\\": "Modules/",\n            "App\\\\": "app/"|' "$PROJECT_DIR/composer.json"
    echo "  ✓ Added App\Modules namespace"
else
    echo "  ✓ Already configured"
fi

# Step 4: Update bootstrap/providers.php - register ModuleServiceProvider
echo "[4/6] Registering ModuleServiceProvider..."
PROVIDERS_FILE="$PROJECT_DIR/bootstrap/providers.php"
if ! grep -q 'Sync.*ModuleServiceProvider' "$PROVIDERS_FILE"; then
    sed -i '/Distribution.*ModuleServiceProvider/a\    \\App\\Modules\\Sync\\Providers\\ModuleServiceProvider::class,' "$PROVIDERS_FILE"
    echo "  ✓ Registered Sync ModuleServiceProvider"
else
    echo "  ✓ Already registered"
fi

# Step 5: Add .env variables
echo "[5/6] Adding .env variables..."
ENV_FILE="$PROJECT_DIR/.env"
if ! grep -q 'SYNC_EXTERNAL_URL' "$ENV_FILE"; then
    cat >> "$ENV_FILE" << 'EOF'

# Sync Server-to-Server
SYNC_EXTERNAL_URL=http://207.231.110.79
SYNC_TOKEN=REPLACE_WITH_YOUR_SECRET_TOKEN
EOF
    echo "  ✓ Added SYNC variables to .env"
    echo "  ⚠ Edit .env and set SYNC_TOKEN to match the local server!"
else
    echo "  ✓ Already configured"
fi

# Step 6: Regenerate autoload and clear caches
echo "[6/6] Regenerating autoload and clearing caches..."
cd "$PROJECT_DIR"
composer dump-autoload
php artisan config:clear
php artisan route:clear

echo ""
echo "============================================="
echo "  ✓ Deployment Complete!"
echo "============================================="
echo ""
echo "Next steps:"
echo "  1. Edit .env: Set SYNC_TOKEN (must match local server)"
echo "  2. Verify: php artisan route:list | findstr v2/sync"
echo "  3. Test: curl -X POST http://localhost/api/v2/sync/receive-invoice"
