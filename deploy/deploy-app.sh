#!/bin/bash

# ========================================
# Axionyx ERP - App Deployment Script
# Run this to deploy/update the application
# ========================================

set -e

APP_DIR="/var/www/axionyx"
DOMAIN="vps-4725-2d60766e.wpressly.com"

echo "=== Deploying Axionyx ERP ==="

# Create app directory
sudo mkdir -p $APP_DIR
sudo chown -R $USER:$USER $APP_DIR

# Clone or copy project (adjust as needed)
# Option 1: If using Git
# git clone your-repo.git $APP_DIR

# Option 2: Copy files via SCP first, then run this

cd $APP_DIR

# Install dependencies
echo "Installing Composer dependencies..."
composer install --optimize-autoloader --no-dev

echo "Installing NPM dependencies..."
npm install

echo "Building frontend assets..."
npm run build

# Setup .env
if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate
    echo "Created .env - PLEASE EDIT IT with production values!"
fi

# Setup SQLite database
if [ ! -f database/database.sqlite ]; then
    touch database/database.sqlite
    echo "Created SQLite database"
fi

# Run migrations
php artisan migrate --force

# Setup storage link
php artisan storage:link

# Cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
sudo chown -R www-data:www-data $APP_DIR
sudo find $APP_DIR -type d -exec chmod 755 {} \;
sudo find $APP_DIR -type f -exec chmod 644 {} \;
sudo chmod -R 775 $APP_DIR/storage
sudo chmod -R 775 $APP_DIR/bootstrap/cache

echo "=== Deployment Complete ==="
echo "Don't forget to:"
echo "1. Edit .env with production values"
echo "2. Setup Nginx config (deploy/nginx.conf)"
echo "3. Setup Supervisor for queue (deploy/supervisor.conf)"
echo "4. Setup SSL with certbot"
