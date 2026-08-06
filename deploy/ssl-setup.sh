#!/bin/bash

# ========================================
# Axionyx ERP - SSL Setup with Certbot
# ========================================

set -e

DOMAIN="vps-4725-2d60766e.wpressly.com"

echo "=== Setting up SSL Certificate ==="

# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Get SSL certificate
sudo certbot --nginx -d $DOMAIN --non-interactive --agree-tos --email admin@$DOMAIN

# Auto-renewal cron
echo "0 12 * * * /usr/bin/certbot renew --quiet" | sudo tee -a /etc/cron.d/certbot-renew

echo "=== SSL Setup Complete ==="
echo "Your site is now available at https://$DOMAIN"
