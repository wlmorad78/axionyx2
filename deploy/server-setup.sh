#!/bin/bash

# ========================================
# Axionyx ERP - Server Setup Script
# Run this ONCE on your fresh Ubuntu server
# ========================================

set -e

echo "=== Axionyx ERP Server Setup ==="

# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.3 + extensions
sudo apt install -y software-properties-common
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update
sudo apt install -y php8.3 php8.3-cli php8.3-fpm php8.3-mbstring php8.3-xml \
    php8.3-curl php8.3-sqlite3 php8.3-bcmath php8.3-intl php8.3-zip \
    php8.3-gd php8.3-redis php8.3-dom php8.3-tokenizer

# Install Nginx
sudo apt install -y nginx

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js (for frontend build)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Install Supervisor (for queue worker)
sudo apt install -y supervisor

# Install SQLite (if not already installed)
sudo apt install -y sqlite3

# Install Git
sudo apt install -y git

# Create deployment user (optional, or use existing)
echo ""
echo "=== Setup Complete ==="
echo "PHP Version: $(php -v | head -1)"
echo "Nginx Version: $(nginx -v 2>&1)"
echo "Composer Version: $(composer -V | head -1)"
echo "Node Version: $(node -v)"
echo ""
echo "Next steps:"
echo "1. Run: bash deploy/deploy-app.sh"
echo "2. Configure your .env file"
echo "3. Set up SSL with certbot"
