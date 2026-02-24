#!/bin/bash
# deploy/deploy.sh
#
# Reusable deployment script for PreShift.
# Run on the server after initial setup to pull latest changes,
# install dependencies, run migrations, build the frontend, and
# restart services.
#
# Usage (run as root):
#   ssh preshift
#   bash /var/www/preshift/deploy/deploy.sh

set -euo pipefail

APP_DIR="/var/www/preshift"
PHP_VERSION="8.4"

cd ${APP_DIR}

echo "==> Pulling latest changes..."
sudo -u preshift git pull origin main

# ── Backend ──────────────────────────────────────────────────────────
echo "==> Installing PHP dependencies..."
cd ${APP_DIR}/api
sudo -u preshift composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Running migrations..."
sudo -u preshift php artisan migrate --force

echo "==> Caching configuration..."
sudo -u preshift php artisan config:cache
sudo -u preshift php artisan route:cache
sudo -u preshift php artisan view:cache

# ── Frontend ─────────────────────────────────────────────────────────
echo "==> Building frontend..."
cd ${APP_DIR}/client
sudo -u preshift npm ci
sudo -u preshift npm run build

# ── Restart services ─────────────────────────────────────────────────
echo "==> Restarting services..."
supervisorctl restart preshift-reverb
supervisorctl restart preshift-queue
systemctl reload php${PHP_VERSION}-fpm
systemctl reload nginx

echo ""
echo "Deploy complete."
