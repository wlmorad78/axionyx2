# =============================================================================
# Deploy Sync Module to External Server (207.231.110.79)
# Run from LOCAL machine (D:\axionyx_erp)
# =============================================================================

# Step 1: Copy the Sync module folder
scp -r "D:\axionyx_erp\Modules\Sync" user@207.231.110.79:/var/www/erp/Modules/

# Step 2: Copy config file
scp "D:\axionyx_erp\config\sync.php" user@207.231.110.79:/var/www/erp/config/

# Step 3: SSH into external server and run commands
ssh user@207.231.110.79

# Then on the external server run:
# cd /var/www/erp
#
# # Add autoload (if not already added)
# sed -i 's|"App\\": "app/"|"App\\Modules\\": "Modules/",\n            "App\\": "app/"|' composer.json
#
# # Register provider (if not already registered)
# sed -i '/Distribution.*ModuleServiceProvider/a\    \App\Modules\Sync\Providers\ModuleServiceProvider::class,' bootstrap/providers.php
#
# # Add env vars
# cat >> .env << 'EOF'
# SYNC_EXTERNAL_URL=http://207.231.110.79
# SYNC_TOKEN=your_shared_secret_here
# EOF
#
# # Clear and rebuild
# composer dump-autoload
# php artisan config:clear
# php artisan route:clear
# php artisan route:list | grep v2/sync
