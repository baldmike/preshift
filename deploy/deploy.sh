#!/bin/bash
# deploy/deploy.sh
#
# Reusable deployment script for PreShift.
# Run on the server after initial setup to pull latest changes,
# install dependencies, run migrations, build the frontend, and
# restart services.
#
# Usage:
#   ssh root@preshift86.com
#   cd /var/www/preshift
#   sudo -u preshift bash deploy/deploy.sh

set -euo pipefail

APP_DIR="/var/www/preshift"
PHP_VERSION="8.3"

cd ${APP_DIR}

echo "==> Pulling latest changes..."
git pull origin main

# ── Backend ──────────────────────────────────────────────────────────
echo "==> Installing PHP dependencies..."
cd ${APP_DIR}/api
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# ── Frontend ─────────────────────────────────────────────────────────
echo "==> Building frontend..."
cd ${APP_DIR}/client
npm ci
npm run build

# ── Restart services ─────────────────────────────────────────────────
echo "==> Restarting services..."
sudo supervisorctl restart preshift-reverb
sudo supervisorctl restart preshift-queue
sudo systemctl reload php${PHP_VERSION}-fpm
sudo systemctl reload nginx

echo ""
echo "Deploy complete."
